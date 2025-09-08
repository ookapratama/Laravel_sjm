<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // DB::unprepared("
        //     CREATE TRIGGER trigger_pin_activation_products
        //     AFTER UPDATE ON `activation_pins` 
        //     FOR EACH ROW
        //     BEGIN 
        //         DECLARE done INT DEFAULT 0;
        //         DECLARE v_product_id INT;
        //         DECLARE v_quantity INT;
        //         DECLARE v_price DECIMAL(15,2);
        //         DECLARE v_current_stock INT;

        //         -- Cursor untuk ambil produk dari package
        //         DECLARE product_cursor CURSOR FOR 
        //             SELECT pp.product_id, pp.quantity, p.price, p.stock
        //             FROM product_packages AS pp
        //             JOIN products p ON pp.product_id = p.id
        //             WHERE pp.package_id = NEW.product_package_id;

        //         DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

        //         -- HANYA JALANKAN jika status berubah dari non-'used' menjadi 'used'
        //         -- DAN product_package_id sudah diisi
        //         IF OLD.status != 'used' AND NEW.status = 'used' AND NEW.product_package_id IS NOT NULL THEN

        //             -- Validasi product package aktif
        //             IF (SELECT is_active FROM packages WHERE id = NEW.product_package_id) = 0 THEN
        //                 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Product package is not active';
        //             END IF;


        //             OPEN product_cursor;

        //             product_loop: LOOP
        //                 FETCH product_cursor INTO v_product_id, v_quantity, v_price, v_current_stock;
        //                 IF done THEN
        //                     LEAVE product_loop;
        //                 END IF;

        //                 -- Validasi stock cukup
        //                 IF v_current_stock < v_quantity THEN
        //                     SIGNAL SQLSTATE '45000';
        //                 END IF;

        //                 -- Insert ke user_products
        //                 INSERT INTO user_products (user_id, product_id, quantity, source, created_at, updated_at)
        //                 VALUES (NEW.used_by, v_product_id, v_quantity, 'pin_activation', NOW(), NOW());

        //                 -- KURANGI STOCK
        //                 UPDATE products 
        //                 SET stock = stock - v_quantity 
        //                 WHERE id = v_product_id;

        //                 -- Insert ke product_sales untuk tracking
        //                 INSERT INTO product_sales (user_id, product_id, quantity, total_price, payment_type, source, created_at, updated_at)
        //                 VALUES (NEW.used_by, v_product_id, v_quantity, 0, 'pin_activation', 'pin_activation', NOW(), NOW());

        //             END LOOP;

        //             CLOSE product_cursor;

        //         END IF;

        //     END

        // ");

        DB::unprepared('
            CREATE TRIGGER update_pos_session_budget_after_insert
            AFTER INSERT ON pos_items
            FOR EACH ROW
            BEGIN
                UPDATE pos_sessions 
                SET 
                    used_budget = used_budget + NEW.total_price,
                    remaining_budget = total_budget - (used_budget + NEW.total_price),
                    products_count = products_count + NEW.quantity
                WHERE id = NEW.session_id;
            END
        ');

        // Trigger untuk update budget ketika item dihapus
        DB::unprepared('
            CREATE TRIGGER update_pos_session_budget_after_delete
            AFTER DELETE ON pos_items
            FOR EACH ROW
            BEGIN
                UPDATE pos_sessions 
                SET 
                    used_budget = GREATEST(0, used_budget - OLD.total_price),
                    remaining_budget = total_budget - used_budget,
                    products_count = GREATEST(0, products_count - OLD.quantity)
                WHERE id = OLD.session_id;
            END
        ');

        // Trigger untuk update budget ketika item diupdate
        DB::unprepared('
            CREATE TRIGGER update_pos_session_budget_after_update
            AFTER UPDATE ON pos_items
            FOR EACH ROW
            BEGIN
                UPDATE pos_sessions 
                SET 
                    used_budget = used_budget - OLD.total_price + NEW.total_price,
                    remaining_budget = total_budget - (used_budget - OLD.total_price + NEW.total_price),
                    products_count = products_count - OLD.quantity + NEW.quantity
                WHERE id = NEW.session_id;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS `trigger_pin_activation_products`');
        DB::unprepared('DROP TRIGGER IF EXISTS update_pos_session_budget_after_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS update_pos_session_budget_after_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS update_pos_session_budget_after_update');
    }
};
