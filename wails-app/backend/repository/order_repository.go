package repository

import (
	"database/sql"
	"pos-desktop/backend/models"
	"strconv"
)

type OrderRepository struct {
	DB *sql.DB
}

func NewOrderRepository(db *sql.DB) *OrderRepository {
	return &OrderRepository{DB: db}
}

func (r *OrderRepository) CreateTransaction(order models.Order, items []models.OrderItem, payment models.Payment) error {
	tx, err := r.DB.Begin()
	if err != nil {
		return err
	}

	// 1. Insert Order
	res, err := tx.Exec("INSERT INTO orders (user_id, total_amount, tax_amount, discount, final_amount, status) VALUES (?, ?, ?, ?, ?, ?)",
		order.UserID, order.TotalAmount, order.TaxAmount, order.Discount, order.FinalAmount, order.Status)
	if err != nil {
		tx.Rollback()
		return err
	}

	orderID, _ := res.LastInsertId()

	// 2. Insert Items & reduce stock
	for _, item := range items {
		productID := interface{}(item.ProductID)
		if item.ProductID == 0 {
			productID = nil
		}
		_, err := tx.Exec("INSERT INTO order_items (order_id, product_id, product_name, quantity, price, discount, total) VALUES (?, ?, ?, ?, ?, ?, ?)",
			orderID, productID, item.ProductName, item.Quantity, item.Price, item.Discount, item.Total)
		if err != nil {
			tx.Rollback()
			return err
		}

		if item.ProductID > 0 {
			_, err = tx.Exec("UPDATE products SET shelf_stock = shelf_stock - ? WHERE id = ?", item.Quantity, item.ProductID)
			if err != nil {
				tx.Rollback()
				return err
			}
		}

		// Record Stock Mutation
		_, err = tx.Exec(`
			INSERT INTO stock_mutations (product_id, type, from_location, to_location, quantity, reference, created_by) 
			VALUES (?, ?, ?, ?, ?, ?, ?)`,
			item.ProductID, "SALE", "RAK", "CUSTOMER", item.Quantity, "Order #"+strconv.FormatInt(orderID, 10), order.UserID)
		if err != nil {
			tx.Rollback()
			return err
		}
	}

	// 3. Insert Payment
	_, err = tx.Exec("INSERT INTO payments (order_id, payment_method, amount_paid, change_amount, status) VALUES (?, ?, ?, ?, ?)",
		orderID, payment.PaymentMethod, payment.AmountPaid, payment.ChangeAmount, payment.Status)
	if err != nil {
		tx.Rollback()
		return err
	}

	return tx.Commit()
}

func (r *OrderRepository) GetDailySales(date string) (float64, error) {
	var total float64
	query := "SELECT COALESCE(SUM(final_amount), 0) FROM orders WHERE date(created_at, 'localtime') = ?"
	err := r.DB.QueryRow(query, date).Scan(&total)
	return total, err
}

func (r *OrderRepository) GetDailyCashSales(date string) (float64, error) {
	var total float64
	query := `
		SELECT COALESCE(SUM(p.amount_paid - p.change_amount), 0)
		FROM orders o
		JOIN payments p ON o.id = p.order_id
		WHERE date(o.created_at, 'localtime') = ? AND p.payment_method = 'Cash'
	`
	err := r.DB.QueryRow(query, date).Scan(&total)
	return total, err
}

func (r *OrderRepository) GetDailyPaymentCounts(date string) (int, int, error) {
	query := `
		SELECT 
			COALESCE(SUM(CASE WHEN p.payment_method = 'Cash' THEN 1 ELSE 0 END), 0) AS cash_count,
			COALESCE(SUM(CASE WHEN p.payment_method != 'Cash' THEN 1 ELSE 0 END), 0) AS non_cash_count
		FROM orders o
		JOIN payments p ON o.id = p.order_id
		WHERE date(o.created_at, 'localtime') = ?
	`
	var cashCount, nonCashCount int
	err := r.DB.QueryRow(query, date).Scan(&cashCount, &nonCashCount)
	return cashCount, nonCashCount, err
}

func (r *OrderRepository) GetMonthlySales(month string) (float64, error) {
	// month pattern: YYYY-MM
	var total float64
	query := "SELECT COALESCE(SUM(final_amount), 0) FROM orders WHERE strftime('%Y-%m', created_at) = ?"
	err := r.DB.QueryRow(query, month).Scan(&total)
	return total, err
}

func (r *OrderRepository) GetOrdersWithItems() ([]map[string]interface{}, error) {
	query := `
		SELECT o.id, o.user_id, u.username, o.total_amount, o.tax_amount, o.discount, o.final_amount, o.created_at, p.payment_method
		FROM orders o
		JOIN users u ON o.user_id = u.id
		JOIN payments p ON o.id = p.order_id
		ORDER BY o.id DESC
	`
	rows, err := r.DB.Query(query)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var results []map[string]interface{}
	for rows.Next() {
		var id, userID int
		var total, tax, discount, final float64
		var createdAt, username, method string
		err := rows.Scan(&id, &userID, &username, &total, &tax, &discount, &final, &createdAt, &method)
		if err != nil {
			return nil, err
		}

		// Get Items for this order
		itemRows, err := r.DB.Query("SELECT product_id, product_name, quantity, price, discount, total FROM order_items WHERE order_id = ?", id)
		if err != nil {
			return nil, err
		}
		var items []models.OrderItem
		for itemRows.Next() {
			var it models.OrderItem
			err := itemRows.Scan(&it.ProductID, &it.ProductName, &it.Quantity, &it.Price, &it.Discount, &it.Total)
			if err != nil {
				itemRows.Close()
				return nil, err
			}
			items = append(items, it)
		}
		itemRows.Close()

		results = append(results, map[string]interface{}{
			"id":             id,
			"user_id":        userID,
			"username":       username,
			"total_amount":   total,
			"tax_amount":     tax,
			"discount":       discount,
			"final_amount":   final,
			"created_at":     createdAt,
			"payment_method": method,
			"items":          items,
		})
	}
	return results, nil
}
