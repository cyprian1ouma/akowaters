-- Drop the foreign key constraint if it exists
ALTER TABLE comments DROP FOREIGN KEY IF EXISTS comments_ibfk_1;

-- Modify the post_id column to allow 0
ALTER TABLE comments MODIFY COLUMN post_id INT NOT NULL DEFAULT 0;

-- Add a new foreign key constraint that allows 0
ALTER TABLE comments ADD CONSTRAINT comments_ibfk_1 
FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE; 