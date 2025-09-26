var app = angular.module("collabNotesApp", []);

// Directive to bind file input to scope
app.directive("fileModel", ["$parse", function($parse) {
  return {
    restrict: "A",
    link: function(scope, element, attrs) {
      var model = $parse(attrs.fileModel);
      element.bind("change", function() {
        scope.$apply(function() {
          model.assign(scope, element[0].files[0]);
        });
      });
    }
  };
}]);

app.controller("NotesController", function($scope, $http, $sce) {
  $scope.notes = [];
  $scope.searchQuery = '';
  $scope.searchActive = false;
  $scope.topLiked = [];
  $scope.topDownloaded = [];
  $scope.newNote = { uploader: "", file: null };
  $scope.previewNote = null;
  $scope.previewUrl = null;
  $scope.editNote = null; // holds the note being edited
  $scope.auth = { authenticated: false, email: "", password: "", user: null };
  $scope.view = 'landing'; // landing | auth | app
  $scope.appTab = 'notes'; // notes | uploads
  $scope.userMenuOpen = false;
  $scope.activeNote = null;
  $scope.comments = [];
  $scope.newComment = { username: '', content: '' };
  $scope.commentsOpen = false;

  $scope.goTo = function(view) {
    $scope.view = view;
  };

  $scope.showTab = function(tab) {
    $scope.appTab = tab;
  };

  $scope.toggleUserMenu = function() {
    $scope.userMenuOpen = !$scope.userMenuOpen;
  };

  // Comments
  $scope.openComments = function(note) {
    $scope.activeNote = note;
    $scope.commentsOpen = true;
    $scope.loadComments();
  };

  $scope.closeComments = function() {
    $scope.commentsOpen = false;
  };

  $scope.loadComments = function() {
    if (!$scope.activeNote) return;
    $http.get("../api/comments_list.php", { params: { note_id: $scope.activeNote.id } })
      .then(function(res) {
        if (res.data && res.data.success) {
          $scope.comments = res.data.comments;
        } else {
          $scope.comments = [];
        }
      });
  };

  $scope.addComment = function() {
    if (!$scope.activeNote || !$scope.newComment.username || !$scope.newComment.content) return;
    var payload = {
      note_id: $scope.activeNote.id,
      username: $scope.newComment.username,
      content: $scope.newComment.content
    };
    $http.post("../api/comments_add.php", payload)
      .then(function(res) {
        if (res.data && res.data.success) {
          $scope.newComment.content = '';
          $scope.loadComments();
        } else {
          alert(res.data.error || 'Failed to add comment');
        }
      });
  };

  // Display helpers
  $scope.displayName = function(filename) {
    if (!filename || typeof filename !== 'string') return filename;
    // Remove leading numeric prefix and underscore: 1234567890_...
    var name = filename.replace(/^\d+_/, '');
    // Remove extension
    var lastDot = name.lastIndexOf('.');
    if (lastDot > 0) {
      name = name.substring(0, lastDot);
    }
    return name;
  };

  // Auth: check current session
  $scope.checkSession = function() {
    return $http.get("../api/me.php").then(function(res) {
      $scope.auth.authenticated = !!res.data.authenticated;
      $scope.auth.user = res.data.user || null;
      return $scope.auth.authenticated;
    });
  };

  // Login
  $scope.login = function() {
    if (!$scope.auth.email || !$scope.auth.password) {
      alert("Enter email and password");
      return;
    }
    $http.post("../api/login.php", { email: $scope.auth.email, password: $scope.auth.password })
      .then(function(res) {
        if (res.data && res.data.success) {
          $scope.auth.authenticated = true;
          $scope.auth.user = res.data.user;
          $scope.view = 'app';
          $scope.loadNotes();
        } else {
          alert(res.data.error || "Login failed");
        }
      });
  };

  // Logout
  $scope.logout = function() {
    $http.post("../api/logout.php", {}).then(function() {
      $scope.auth = { authenticated: false, email: "", password: "", user: null };
      $scope.notes = [];
      $scope.view = 'landing';
    });
  };
  
  // Load all notes
  $scope.loadNotes = function() {
    $http.get("../api/list.php").then(function(response) {
      $scope.notes = response.data;
      // compute top lists (top 10)
      var byLikes = (response.data || []).slice().sort(function(a,b){ return (b.votes||0) - (a.votes||0); });
      var byDownloads = (response.data || []).slice().sort(function(a,b){ return (b.downloads||0) - (a.downloads||0); });
      $scope.topLiked = byLikes.slice(0, 10);
      $scope.topDownloaded = byDownloads.slice(0, 10);
    });
  };

  // Real-time filter for notes by uploader or filename (display name)
  $scope.notesFilter = function(note) {
    if (!$scope.searchActive || !$scope.searchQuery) return true;
    var q = ($scope.searchQuery || '').toString().toLowerCase();
    var uploader = (note.uploader || '').toString().toLowerCase();
    var filename = (note.filename || '').toString().toLowerCase();
    // Also check prettified display name
    var display = ($scope.displayName(note.filename) || '').toString().toLowerCase();
    return uploader.indexOf(q) !== -1 || filename.indexOf(q) !== -1 || display.indexOf(q) !== -1;
  };

  // Upload new note
  $scope.uploadNote = function() {
    if (!$scope.newNote.file || !$scope.newNote.uploader) {
      alert("Please enter your name and select a file.");
      return;
    }

    var formData = new FormData();
    formData.append("uploader", $scope.newNote.uploader);
    formData.append("pdf", $scope.newNote.file);

    $http.post("../api/upload.php", formData, {
      headers: { "Content-Type": undefined }
    }).then(function(response) {
      if (response.data.success) {
        alert("File uploaded successfully!");
        $scope.loadNotes();
        $scope.newNote.file = null;
        document.getElementById("fileInput").value = "";
      } else {
        alert(response.data.error || "Upload failed.");
      }
    });
  };

 // === VOTE LOGIC ===
  $scope.voteNote = function(note) {
    $http.post("../api/vote.php", { id: note.id }, {
      headers: { "Content-Type": "application/json" } // send JSON
    }).then(function(response) {
      if (response.data.success) {
        note.votes = response.data.votes; // update counter
      } else {
        alert(response.data.error || "Could not vote for the note.");
      }
    });
  };

  // === EDIT MODAL LOGIC ===
  // Open edit modal - Updated 2025-09-25
  $scope.openEdit = function(note) {
    console.log("Opening edit modal for note:", note);
    console.log("Current user:", $scope.auth.user);
    
    // Only allow editing if user owns the note
    if (note.uploader !== $scope.auth.user.name) {
      alert("You can only edit your own uploaded files.");
      return;
    }
    
    $scope.editNote = angular.copy(note); // clone original note
    $scope.editNote.newFile = null;
    
    console.log("Edit note initialized:", $scope.editNote);
    
    // Clear any previous file input
    setTimeout(function() {
      var fileInput = document.getElementById("editFileInput");
      if (fileInput) {
        fileInput.value = "";
        console.log("File input cleared");
      }
    }, 100);
  };

  // Close edit modal
  $scope.closeEdit = function() {
    $scope.editNote = null;
  };

  // Confirm update from modal - Updated 2025-09-25
  $scope.confirmUpdate = function() {
    // Debug log to check what we have
    console.log("Edit validation - newFile:", $scope.editNote.newFile);
    console.log("Edit validation - editNote:", $scope.editNote);
    
    if (!$scope.editNote || !$scope.editNote.newFile) {
      alert("Please select a PDF file to upload.");
      return;
    }

    var formData = new FormData();
    formData.append("note_id", $scope.editNote.id);
    formData.append("pdf", $scope.editNote.newFile);

    $http.post("../api/update.php", formData, {
      headers: { "Content-Type": undefined }
    }).then(function(response) {
      if (response.data.success) {
        alert("File updated successfully!");
        $scope.closeEdit();
        $scope.loadNotes();
      } else {
        alert(response.data.error || "Error updating file.");
      }
    });
  };

  // === PREVIEW LOGIC ===
  // Open PDF Preview
  $scope.openPreview = function(note) {
    $scope.previewNote = note;
    $scope.previewUrl = $sce.trustAsResourceUrl("../uploads/" + note.filename);
  };

  // Close PDF Preview
  $scope.closePreview = function() {
    $scope.previewNote = null;
    $scope.previewUrl = null;
  };

  // === DOWNLOAD LOGIC ===
  $scope.downloadNote = function(note) {
    if (!note || !note.id) return;
    var url = "../api/download.php?id=" + encodeURIComponent(note.id);
    // Open in a hidden iframe to preserve single-page app context and trigger download
    var iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = url;
    document.body.appendChild(iframe);
    // Optimistically increment UI counter
    if (typeof note.downloads === 'number') {
      note.downloads += 1;
    } else {
      note.downloads = 1;
    }
    // Clean up iframe after some time
    setTimeout(function() { document.body.removeChild(iframe); }, 60000);
  };

  // Initial load: check auth then load
  $scope.checkSession().then(function(isAuthed) {
    if (isAuthed) {
      $scope.view = 'app';
      $scope.loadNotes();
    }
  });
});
