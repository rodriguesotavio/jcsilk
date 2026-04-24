ALTER TABLE produtos
ADD COLUMN estoque_minimo INT NOT NULL DEFAULT 5 AFTER quantidade_estoque;

