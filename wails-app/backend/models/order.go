package models

import "time"

type Order struct {
	ID           uint        `json:"id" gorm:"primaryKey"`
	UserID       uint        `json:"user_id"`
	TotalAmount  float64     `json:"total_amount"`
	TaxAmount    float64     `json:"tax_amount"`
	Discount     float64     `json:"discount"`
	FinalAmount  float64     `json:"final_amount"`
	AmountPaid   float64     `json:"amount_paid"`   // Tambahkan field ini
	ChangeAmount float64     `json:"change_amount"` // Tambahkan field ini
	Status       string      `json:"status"`
	CreatedAt    time.Time   `json:"created_at"`
	UpdatedAt    time.Time   `json:"updated_at"`
	Items        []OrderItem `json:"items" gorm:"foreignKey:OrderID"` // Tambahkan field ini
}
