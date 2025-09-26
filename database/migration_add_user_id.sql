-- Migration to add user_id column to notes table
-- This allows for proper user-based authentication for edit operations

USE collab_notes;

-- Add user_id column to notes table
ALTER TABLE notes ADD COLUMN user_id INT DEFAULT NULL;

-- Add foreign key constraint
ALTER TABLE notes ADD CONSTRAINT fk_notes_user 
    FOREIGN KEY (user_id) REFERENCES users(id) 
    ON DELETE SET NULL;

-- Add index for better performance
CREATE INDEX idx_notes_user_id ON notes(user_id);

-- Update existing notes to have user_id based on uploader name matching
-- This is a one-time migration for existing data
UPDATE notes n 
JOIN users u ON n.uploader = u.name 
SET n.user_id = u.id 
WHERE n.user_id IS NULL;
