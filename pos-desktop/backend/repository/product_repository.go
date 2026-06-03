package repository

import (
	"database/sql"
	"pos-desktop/backend/models"
)

type ProductRepository struct {
	DB *sql.DB
}

func NewProductRepository(db *sql.DB) *ProductRepository {
	return &ProductRepository{DB: db}
}

func (r *ProductRepository) GetAll() ([]models.Product, error) {
	query := `SELECT id, name, barcode, cost_price, selling_price, shelf_stock, warehouse_stock FROM products`
	rows, err := r.DB.Query(query)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var products []models.Product
	for rows.Next() {
		var p models.Product
		if err := rows.Scan(&p.ID, &p.Name, &p.Barcode, &p.CostPrice, &p.SellingPrice, &p.ShelfStock, &p.WarehouseStock); err != nil {
			return nil, err
		}
		products = append(products, p)
	}
	return products, nil
}

func (r *ProductRepository) GetByID(id int) (*models.Product, error) {
	var p models.Product
	err := r.DB.QueryRow("SELECT id, name, barcode, cost_price, selling_price, shelf_stock, warehouse_stock FROM products WHERE id = ?", id).
		Scan(&p.ID, &p.Name, &p.Barcode, &p.CostPrice, &p.SellingPrice, &p.ShelfStock, &p.WarehouseStock)
	if err != nil {
		return nil, err
	}
	return &p, nil
}
func (r *ProductRepository) GetByBarcode(barcode string) (*models.Product, error) {
	var p models.Product
	err := r.DB.QueryRow("SELECT id, name, barcode, cost_price, selling_price, shelf_stock, warehouse_stock FROM products WHERE barcode = ?", barcode).
		Scan(&p.ID, &p.Name, &p.Barcode, &p.CostPrice, &p.SellingPrice, &p.ShelfStock, &p.WarehouseStock)
	if err != nil {
		return nil, err
	}
	return &p, nil
}

func (r *ProductRepository) Create(p models.Product) error {
	_, err := r.DB.Exec("INSERT INTO products (name, barcode, cost_price, selling_price, shelf_stock, warehouse_stock) VALUES (?, ?, ?, ?, ?, ?)",
		p.Name, p.Barcode, p.CostPrice, p.SellingPrice, p.ShelfStock, p.WarehouseStock)
	return err
}

func (r *ProductRepository) Upsert(p models.Product) error {
	query := `
		INSERT INTO products (name, barcode, cost_price, selling_price, shelf_stock, warehouse_stock)
		VALUES (?, ?, ?, ?, ?, ?)
		ON CONFLICT(barcode) DO UPDATE SET
			name = excluded.name,
			cost_price = excluded.cost_price,
			selling_price = excluded.selling_price,
			shelf_stock = excluded.shelf_stock,
			warehouse_stock = excluded.warehouse_stock
	`
	_, err := r.DB.Exec(query, p.Name, p.Barcode, p.CostPrice, p.SellingPrice, p.ShelfStock, p.WarehouseStock)
	return err
}

func (r *ProductRepository) Update(p models.Product) error {
	_, err := r.DB.Exec("UPDATE products SET name = ?, barcode = ?, cost_price = ?, selling_price = ?, shelf_stock = ?, warehouse_stock = ? WHERE id = ?",
		p.Name, p.Barcode, p.CostPrice, p.SellingPrice, p.ShelfStock, p.WarehouseStock, p.ID)
	return err
}

func (r *ProductRepository) RecordMutation(m models.StockMutation) error {
	_, err := r.DB.Exec(`
		INSERT INTO stock_mutations (product_id, type, from_location, to_location, quantity, reference, created_by) 
		VALUES (?, ?, ?, ?, ?, ?, ?)`,
		m.ProductID, m.Type, m.FromLocation, m.ToLocation, m.Quantity, m.Reference, m.CreatedBy)
	return err
}

func (r *ProductRepository) GetMutations() ([]models.StockMutation, error) {
	query := `
		SELECT sm.id, sm.product_id, p.name, sm.type, sm.from_location, sm.to_location, sm.quantity, sm.reference, sm.created_at
		FROM stock_mutations sm
		JOIN products p ON sm.product_id = p.id
		ORDER BY sm.created_at DESC
	`
	rows, err := r.DB.Query(query)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var mutations []models.StockMutation
	for rows.Next() {
		var m models.StockMutation
		err := rows.Scan(&m.ID, &m.ProductID, &m.ProductName, &m.Type, &m.FromLocation, &m.ToLocation, &m.Quantity, &m.Reference, &m.CreatedAt)
		if err != nil {
			return nil, err
		}
		mutations = append(mutations, m)
	}
	return mutations, nil
}

func (r *ProductRepository) Delete(id int) error {
	_, err := r.DB.Exec("DELETE FROM products WHERE id = ?", id)
	return err
}

type CategoryRepository struct {
	DB *sql.DB
}

func NewCategoryRepository(db *sql.DB) *CategoryRepository {
	return &CategoryRepository{DB: db}
}

func (r *CategoryRepository) GetAll() ([]models.Category, error) {
	rows, err := r.DB.Query("SELECT id, name FROM categories")
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var cats []models.Category
	for rows.Next() {
		var c models.Category
		if err := rows.Scan(&c.ID, &c.Name); err != nil {
			return nil, err
		}
		cats = append(cats, c)
	}
	return cats, nil
}

func (r *CategoryRepository) Create(c models.Category) error {
	_, err := r.DB.Exec("INSERT INTO categories (name) VALUES (?)", c.Name)
	return err
}

func (r *CategoryRepository) Delete(id int) error {
	_, err := r.DB.Exec("DELETE FROM categories WHERE id = ?", id)
	return err
}
