<?php
/**
 * Plugin Name: Reder Certificados
 * Plugin URI: https://github.com/fabianoaljava/redercertificados
 * Description: Custom plugin for Reder Certificados E-commerce
 * Version: 1.0
 * Author: Fabiano Aljava
 * Author URI: http://www.aljava.net
 */


/*
 * Add custom endpoint that appears in My Account Page - WooCommerce
 */


add_filter ( 'woocommerce_account_menu_items', 'reder_certificados', 40 );
function reder_certificados( $menu_links ){
 
	$menu_links = array_slice( $menu_links, 0, 5, true ) 
	+ array( 'certificados' => 'Meus Certificados' )
	+ array( 'agendamento'  =>  'Agendamento' )
	+ array_slice( $menu_links, 5, NULL, true );
 
	return $menu_links;
 
}

add_action( 'init', 'reder_add_endpoint' );

function reder_add_endpoint() {
 	
	add_rewrite_endpoint( 'certificados', EP_PAGES );
	add_rewrite_endpoint( 'agendamento', EP_PAGES );
 
}

add_action( 'woocommerce_account_certificados_endpoint', 'reder_my_account_certificados_content' );

function reder_my_account_certificados_content() {
	
	
  $result_array = array();
  $users = get_users();
  $user_id = get_current_user_id();
  $current_user= wp_get_current_user();

  if($users) {

    foreach ($users as $user) {
		
		
	  if ($user->ID == $user_id) {		  
	  
		  $products_ordered = array();

		  $order_args = array(
			'posts_per_page' => -1,
			'meta_key'    => '_customer_user',
			'meta_value'  => $user->ID,
			'post_type'   => 'shop_order',
			'post_status' => 'wc-completed', // only get completed orders
		  );
		  $orders = get_posts($order_args);

		  if($orders) {
			foreach ($orders as $order) {
			  $wc_order = wc_get_order( $order->ID );
				
			
			  
				
			  $order_id = $wc_order->ID;
				
			  $order_date = new DateTime($wc_order->order_date);
			  
			  
			
			  //print_r($wc_order);
			  //echo "<hr>";

			  foreach ($wc_order->get_items() as $product) {
				$wc_product = wc_get_product($product->get_product_id());
				
				  
				  
				  
				  //print_r($product);
				  //echo "<hr>";

				if($wc_product) { // make sure the product still exists
				  if( ! isset( $products_ordered[$product->get_product_id()] ) ) {
					  
					$variation = wc_get_product( $product->get_variation_id() );					  
					$product_url = $variation->get_permalink();
					  
					
					  
					$validade = substr($variation->get_attribute('validade'),0, 2);					
					$interval = new DateInterval('P'.$validade.'M');
					  
					$expirationdate = new DateTime($wc_order->order_date);
					$expirationdate->add($interval);
					//echo $expirationdate->format('Y-m-d') . "\n";
					  
					$product_expiration = $expirationdate->format('d/m/Y');
					  
					$product_protocol = $order_date->format('Ymd') . $order_id;
					  
					  
					if ($expirationdate < date()) {
						$cert_status = "Vencido";
					} else {
						$cert_status = "A vencer";
					}
					  
					$products_ordered[$product->get_product_id()] = array(
					  'ID' => $order_id,
					  'protocolo' => $product_protocol,
					  'nome' => $product->get_name(),
					  'url' => $product_url,
					  'datapagamento' => $order_date->format('d/m/Y'),
					  'dataexpiracao' => $product_expiration,
					  'status' => $cert_status,
					  'renovar' => wp_nonce_url( add_query_arg( 'order_again', $order_id) , 'woocommerce-order_again' )
					);
				  } else {
					$products_ordered[$product->get_product_id()]['qty'] = $products_ordered[$product->get_product_id()]['qty'] + $product->get_quantity();
				  }


				}
			  }
			}
		  }

		  $customer = new WC_Customer( $user->ID );
		
		  $billing_data = $customer->get_billing('view');

		  // we have collected all data, save it to array
		  $result_array[$user->ID] = array(
			'products_ordered' => $products_ordered
		  );

		}
	
	}
  }

  // shortcode html output begins hebrev
  $return_html = '<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table"><thead><tr><th class="woocommerce-orders-table__header">Protocolo</th><th  class="woocommerce-orders-table__header">Produto</th><th class="woocommerce-orders-table__header">Data Pagamento</th><th class="woocommerce-orders-table__header">Data Expiração</th><th class="woocommerce-orders-table__header">Status</th><th class="woocommerce-orders-table__header">Ações</th></tr></thead><tbody>';

  foreach ($result_array as $user_id => $data) {
    if( isset($data['products_ordered']) && $data['products_ordered'] ) {
      foreach ($data['products_ordered'] as $product_id => $product_data) {
        $return_html .= '<tr>';
        $return_html .= '<td><a href="/minha-conta/view-order/'.$product_data['ID'].'">'.$product_data['protocolo'].'</a></td>';
        $return_html .= '<td><a href="'.$product_data['url'].'">'.$product_data['nome'].'</a></td>';
        $return_html .= '<td>'.$product_data['datapagamento'].'</td>';
		$return_html .= '<td>'.$product_data['dataexpiracao'].'</td>';
		$return_html .= '<td>'.$product_data['status'].'</td>';
		$return_html .= '<td><a href="'.$product_data['renovar'].'" class="button">Renovar</a></td>';
        $return_html .= '</tr>';
      }
    }
  }

  $return_html .= '</tbody></table>';
  echo '<h2 class="avada-woocommerce-myaccount-heading" data-fontsize="38" data-lineheight="47">
				Meus Certificados</h2>';
  echo $return_html;
 
}

add_action( 'woocommerce_account_agendamento_endpoint', 'reder_my_account_agendamento_content' );

function reder_my_account_agendamento_content() {
  echo '<h2 class="avada-woocommerce-myaccount-heading" data-fontsize="38" data-lineheight="47">
				Agendamento</h2>';
	
	echo do_shortcode( '[ea_bootstrap]' );
	
	
}


function wpb_woo_my_account_order() {
	$myorder = array(
		'certificados' 		 => __( 'Meus Certificados', 'woocommerce' ),
		'agendamento' 		 => __( 'Agendamento', 'woocommerce' ),
		'downloads'          => __( 'Downloads', 'woocommerce' ),
		'edit-account'       => __( 'Detalhes da conta', 'woocommerce' ),		
		'orders'             => __( 'Meus pedidos', 'woocommerce' ),		
		'edit-address'       => __( 'Endereços', 'woocommerce' ),
		'payment-methods'    => __( 'Formas de Pagamento', 'woocommerce' ),
		'customer-logout'    => __( 'Sair', 'woocommerce' ),
	);
	return $myorder;
}
add_filter ( 'woocommerce_account_menu_items', 'wpb_woo_my_account_order' );

?>