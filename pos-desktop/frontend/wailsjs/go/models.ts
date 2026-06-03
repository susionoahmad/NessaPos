export namespace models {
	
	export class CashierSession {
	    id: number;
	    user_id: number;
	    username: string;
	    start_time: string;
	    end_time: string;
	    status: string;
	    start_amount: number;
	    end_amount_calculated: number;
	    end_amount_physical: number;
	    difference: number;
	    start_denoms: string;
	    end_denoms: string;
	
	    static createFrom(source: any = {}) {
	        return new CashierSession(source);
	    }
	
	    constructor(source: any = {}) {
	        if ('string' === typeof source) source = JSON.parse(source);
	        this.id = source["id"];
	        this.user_id = source["user_id"];
	        this.username = source["username"];
	        this.start_time = source["start_time"];
	        this.end_time = source["end_time"];
	        this.status = source["status"];
	        this.start_amount = source["start_amount"];
	        this.end_amount_calculated = source["end_amount_calculated"];
	        this.end_amount_physical = source["end_amount_physical"];
	        this.difference = source["difference"];
	        this.start_denoms = source["start_denoms"];
	        this.end_denoms = source["end_denoms"];
	    }
	}
	export class CashierTransaction {
	    id: number;
	    session_id: number;
	    user_id: number;
	    username: string;
	    type: string;
	    amount: number;
	    balance_after: number;
	    description: string;
	    created_at: string;
	
	    static createFrom(source: any = {}) {
	        return new CashierTransaction(source);
	    }
	
	    constructor(source: any = {}) {
	        if ('string' === typeof source) source = JSON.parse(source);
	        this.id = source["id"];
	        this.session_id = source["session_id"];
	        this.user_id = source["user_id"];
	        this.username = source["username"];
	        this.type = source["type"];
	        this.amount = source["amount"];
	        this.balance_after = source["balance_after"];
	        this.description = source["description"];
	        this.created_at = source["created_at"];
	    }
	}
	export class Category {
	    id: number;
	    name: string;
	    created_at: string;
	    updated_at: string;
	
	    static createFrom(source: any = {}) {
	        return new Category(source);
	    }
	
	    constructor(source: any = {}) {
	        if ('string' === typeof source) source = JSON.parse(source);
	        this.id = source["id"];
	        this.name = source["name"];
	        this.created_at = source["created_at"];
	        this.updated_at = source["updated_at"];
	    }
	}
	export class OrderItem {
	    id: number;
	    order_id: number;
	    product_id: number;
	    product_name: string;
	    quantity: number;
	    price: number;
	    discount: number;
	    total: number;
	    // Go type: time
	    created_at: any;
	    // Go type: time
	    updated_at: any;
	
	    static createFrom(source: any = {}) {
	        return new OrderItem(source);
	    }
	
	    constructor(source: any = {}) {
	        if ('string' === typeof source) source = JSON.parse(source);
	        this.id = source["id"];
	        this.order_id = source["order_id"];
	        this.product_id = source["product_id"];
	        this.product_name = source["product_name"];
	        this.quantity = source["quantity"];
	        this.price = source["price"];
	        this.discount = source["discount"];
	        this.total = source["total"];
	        this.created_at = this.convertValues(source["created_at"], null);
	        this.updated_at = this.convertValues(source["updated_at"], null);
	    }
	
		convertValues(a: any, classs: any, asMap: boolean = false): any {
		    if (!a) {
		        return a;
		    }
		    if (a.slice && a.map) {
		        return (a as any[]).map(elem => this.convertValues(elem, classs));
		    } else if ("object" === typeof a) {
		        if (asMap) {
		            for (const key of Object.keys(a)) {
		                a[key] = new classs(a[key]);
		            }
		            return a;
		        }
		        return new classs(a);
		    }
		    return a;
		}
	}
	export class Product {
	    id: number;
	    name: string;
	    barcode: string;
	    cost_price: number;
	    selling_price: number;
	    shelf_stock: number;
	    warehouse_stock: number;
	    created_at: string;
	    updated_at: string;
	
	    static createFrom(source: any = {}) {
	        return new Product(source);
	    }
	
	    constructor(source: any = {}) {
	        if ('string' === typeof source) source = JSON.parse(source);
	        this.id = source["id"];
	        this.name = source["name"];
	        this.barcode = source["barcode"];
	        this.cost_price = source["cost_price"];
	        this.selling_price = source["selling_price"];
	        this.shelf_stock = source["shelf_stock"];
	        this.warehouse_stock = source["warehouse_stock"];
	        this.created_at = source["created_at"];
	        this.updated_at = source["updated_at"];
	    }
	}
	export class Setting {
	    id: number;
	    store_name: string;
	    store_address: string;
	    store_phone: string;
	    tax_rate: number;
	    tax_type: string;
	    receipt_text: string;
	    printer_name: string;
	    refresh_interval_sec: number;
	    print_session_slip: boolean;
	    cash_drawer_enabled: boolean;
	    trial_start: string;
	    last_run: string;
	    license_blob: string;
	    updated_at: string;
	
	    static createFrom(source: any = {}) {
	        return new Setting(source);
	    }
	
	    constructor(source: any = {}) {
	        if ('string' === typeof source) source = JSON.parse(source);
	        this.id = source["id"];
	        this.store_name = source["store_name"];
	        this.store_address = source["store_address"];
	        this.store_phone = source["store_phone"];
	        this.tax_rate = source["tax_rate"];
	        this.tax_type = source["tax_type"];
	        this.receipt_text = source["receipt_text"];
	        this.printer_name = source["printer_name"];
	        this.refresh_interval_sec = source["refresh_interval_sec"];
	        this.print_session_slip = source["print_session_slip"];
	        this.cash_drawer_enabled = source["cash_drawer_enabled"];
	        this.trial_start = source["trial_start"];
	        this.last_run = source["last_run"];
	        this.license_blob = source["license_blob"];
	        this.updated_at = source["updated_at"];
	    }
	}
	export class StockMutation {
	    id: number;
	    product_id: number;
	    product_name: string;
	    type: string;
	    from_location: string;
	    to_location: string;
	    quantity: number;
	    reference: string;
	    created_at: string;
	    created_by: number;
	
	    static createFrom(source: any = {}) {
	        return new StockMutation(source);
	    }
	
	    constructor(source: any = {}) {
	        if ('string' === typeof source) source = JSON.parse(source);
	        this.id = source["id"];
	        this.product_id = source["product_id"];
	        this.product_name = source["product_name"];
	        this.type = source["type"];
	        this.from_location = source["from_location"];
	        this.to_location = source["to_location"];
	        this.quantity = source["quantity"];
	        this.reference = source["reference"];
	        this.created_at = source["created_at"];
	        this.created_by = source["created_by"];
	    }
	}
	export class User {
	    id: number;
	    username: string;
	    password: string;
	    role: string;
	    created_at: string;
	    updated_at: string;
	
	    static createFrom(source: any = {}) {
	        return new User(source);
	    }
	
	    constructor(source: any = {}) {
	        if ('string' === typeof source) source = JSON.parse(source);
	        this.id = source["id"];
	        this.username = source["username"];
	        this.password = source["password"];
	        this.role = source["role"];
	        this.created_at = source["created_at"];
	        this.updated_at = source["updated_at"];
	    }
	}
	export class Vault {
	    id: number;
	    balance: number;
	    updated_at: string;
	
	    static createFrom(source: any = {}) {
	        return new Vault(source);
	    }
	
	    constructor(source: any = {}) {
	        if ('string' === typeof source) source = JSON.parse(source);
	        this.id = source["id"];
	        this.balance = source["balance"];
	        this.updated_at = source["updated_at"];
	    }
	}
	export class VaultTransaction {
	    id: number;
	    type: string;
	    amount: number;
	    balance_after: number;
	    description: string;
	    created_at: string;
	    created_by: number;
	    username: string;
	
	    static createFrom(source: any = {}) {
	        return new VaultTransaction(source);
	    }
	
	    constructor(source: any = {}) {
	        if ('string' === typeof source) source = JSON.parse(source);
	        this.id = source["id"];
	        this.type = source["type"];
	        this.amount = source["amount"];
	        this.balance_after = source["balance_after"];
	        this.description = source["description"];
	        this.created_at = source["created_at"];
	        this.created_by = source["created_by"];
	        this.username = source["username"];
	    }
	}

}

export namespace services {
	
	export class LicenseStatus {
	    status: string;
	    reason: string;
	    trial_days_left: number;
	    trial_ends_at: string;
	    device_id: string;
	    licensee: string;
	    license_expiry: string;
	
	    static createFrom(source: any = {}) {
	        return new LicenseStatus(source);
	    }
	
	    constructor(source: any = {}) {
	        if ('string' === typeof source) source = JSON.parse(source);
	        this.status = source["status"];
	        this.reason = source["reason"];
	        this.trial_days_left = source["trial_days_left"];
	        this.trial_ends_at = source["trial_ends_at"];
	        this.device_id = source["device_id"];
	        this.licensee = source["licensee"];
	        this.license_expiry = source["license_expiry"];
	    }
	}
	export class OrderRequest {
	    user_id: number;
	    total_amount: number;
	    tax_amount: number;
	    discount: number;
	    final_amount: number;
	    items: models.OrderItem[];
	    payment_method: string;
	    amount_paid: number;
	    change_amount: number;
	
	    static createFrom(source: any = {}) {
	        return new OrderRequest(source);
	    }
	
	    constructor(source: any = {}) {
	        if ('string' === typeof source) source = JSON.parse(source);
	        this.user_id = source["user_id"];
	        this.total_amount = source["total_amount"];
	        this.tax_amount = source["tax_amount"];
	        this.discount = source["discount"];
	        this.final_amount = source["final_amount"];
	        this.items = this.convertValues(source["items"], models.OrderItem);
	        this.payment_method = source["payment_method"];
	        this.amount_paid = source["amount_paid"];
	        this.change_amount = source["change_amount"];
	    }
	
		convertValues(a: any, classs: any, asMap: boolean = false): any {
		    if (!a) {
		        return a;
		    }
		    if (a.slice && a.map) {
		        return (a as any[]).map(elem => this.convertValues(elem, classs));
		    } else if ("object" === typeof a) {
		        if (asMap) {
		            for (const key of Object.keys(a)) {
		                a[key] = new classs(a[key]);
		            }
		            return a;
		        }
		        return new classs(a);
		    }
		    return a;
		}
	}
	export class SessionClosePrintRequest {
	    user_id: number;
	    username: string;
	    start_amount: number;
	    cash_sales: number;
	    expected: number;
	    physical: number;
	    difference: number;
	    denoms: string;
	
	    static createFrom(source: any = {}) {
	        return new SessionClosePrintRequest(source);
	    }
	
	    constructor(source: any = {}) {
	        if ('string' === typeof source) source = JSON.parse(source);
	        this.user_id = source["user_id"];
	        this.username = source["username"];
	        this.start_amount = source["start_amount"];
	        this.cash_sales = source["cash_sales"];
	        this.expected = source["expected"];
	        this.physical = source["physical"];
	        this.difference = source["difference"];
	        this.denoms = source["denoms"];
	    }
	}
	export class SessionOpenPrintRequest {
	    user_id: number;
	    username: string;
	    amount: number;
	    denoms: string;
	
	    static createFrom(source: any = {}) {
	        return new SessionOpenPrintRequest(source);
	    }
	
	    constructor(source: any = {}) {
	        if ('string' === typeof source) source = JSON.parse(source);
	        this.user_id = source["user_id"];
	        this.username = source["username"];
	        this.amount = source["amount"];
	        this.denoms = source["denoms"];
	    }
	}

}

