<?php
/**
 * Plugin Name: WFCM Whatsapp Checkout
 * Description: Whatsapp chechout plugin for WFCM.
 * Version: 1.0
 * Author: aditbaco, Plonknimbuzz
 * Author URI: https://github.com/aditbaco/WA-Checkout-WCFM-Woocommerce
 */

//register Whatsapp number to vendors store setting
add_filter( 'wcfm_marketplace_settings_fields_address', 'vendor_store_custom_fields' );
function vendor_store_custom_fields($settings_fields_general) {
	global $WCFM, $WCFMmp, $wp;
	if( current_user_can('administrator') ) {
		$van_cur_url = add_query_arg( array(), $wp->request );
		$van_vendorid = substr( $van_cur_url, strrpos( $van_cur_url, '/' ) + 1 );
		$user_id = intval( $van_vendorid );
	}
	else {
		$user_id = apply_filters( 'wcfm_current_vendor_id', get_current_user_id() );
	}
	$store_whatsapp_opt = array( 'yes' => __( 'Yes', 'wc-frontend-manager' ), 'no' => __( 'No', 'wc-frontend-manager' ) );
	$vendor_data = get_user_meta( $user_id, 'wcfmmp_profile_settings', true );
	$store_whatsapp = isset( $vendor_data['store_whatsapp_number'] ) ? $vendor_data['store_whatsapp_number'] : 'no';
	$settings_fields_general["store_whatsapp_number"] = array('label' => __('Show Whatsapp Number', 'wc-frontend-manager') , 'type' => 'select', 'options' => $store_whatsapp_opt, 'class' => 'wcfm-select wcfm_ele wcfm-banner-uploads', 'label_class' => 'wcfm_title', 'value' => $store_whatsapp );
	return $settings_fields_general;
}

add_action( 'after_wcfmmp_sold_by_info_product_page', 'cus_after_wcfmmp_sold_by_info_product_page' );
function cus_after_wcfmmp_sold_by_info_product_page( $vendor_id ) {
	$vendor_data = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
	$whatsapp = get_user_meta( $vendor_id, 'whatsapp-number', true );
	if( $vendor_data['store_whatsapp_number'] == 'yes' ) {
		echo '<div class="wcfmmp_store_tab_info wcfmmp_store_info_address"><i class="wcfmfa fa-phone" aria-hidden="true"></i><span>' . $whatsapp . '</div>';
	}
}

//Register different WA number checkout based on WA number on vendor stores setting
add_action( 'woocommerce_before_thankyou', 'wfcm_add_assets_wa_checkout' );
add_filter( 'woocommerce_thankyou_order_received_text', 'wfcm_wa_thankyou', 10, 2 );

function wfcm_wa_thankyou($title, $order) {
	$wa='';
	$items = "";
	//$mode = ($order->get_billing_address_1() != $order->get_shipping_address_1() || $order->get_billing_first_name() != $order->get_shipping_first_name())?'shipping':'billing';
	$mode = 'shipping'; //force shipping mode
	$country =  WC()->countries->countries[ $order->{"get_".$mode."_country"}() ];
	$states = WC()->countries->get_states( $order->{"get_".$mode."_country"}() );
	$province =  $states[ $order->{"get_".$mode."_state"}() ];
	$shipping_method_title = $order->get_shipping_method();
	if(empty($shipping_method_title)){
		foreach( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ){print_r($shipping_item_obj);
			$shipping_method_title = $shipping_item_obj->get_method_title();
			break;
		}
	}
	
	foreach($order->get_items() as $item){
		$vendor_id = $item->get_meta('_vendor_id');
		$vendor_data = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
		$whatsapp = get_user_meta( $vendor_id, 'whatsapp-number', true );
		$vendor_name =  get_user_meta( $vendor_id, 'store_name', true );
		if(!empty($whatsapp) && !in_array($whatsapp, $whatsapps)){
			$wa=$whatsapp;
			$whatsapps[] = $whatsapp;
			$items .= $item->get_quantity()."x - *".$item->get_name()."*\n";
		    $items .= "Tautan: ".get_permalink( $item->get_product_id() ) ."\n";
		    $judul = 'Terima kasih sudah berbelanja di FNGTMart - Aksi Nyata Peduli Alumni';
        	$subtitle = 'Selesaikan checkout Anda dengan menekan tombol Order by WA dibawah ini agar pesanan dapat diproses oleh Penjual.';
        	$msg = "*Hello, here's my order details:*\n";
        	$msg .= $items."\n";
        	$msg .="*Order Id*: ".$order->get_id()."\n";
        	$msg .="*Harga Total*: ".strip_tags(wc_price($order->get_total()))."\n";
        	$msg .="*Metode Pembayaran*: ".$order->get_payment_method_title()."\n";
        	$msg .="*Metode Pengiriman*: ".$shipping_method_title."\n\n";
        	$msg .="*Info Pengiriman*: \n";
        	$msg .="Nama: ".$order->{"get_".$mode."_first_name"}()." ".$order->{"get_".$mode."_last_name"}()."\n";
        	$msg .="Alamat: ".implode(', ',[$order->{"get_".$mode."_address_1"}(),$order->{"get_".$mode."_address_2"}()])."\n";
        	$msg .="Kota: ".$order->{"get_".$mode."_city"}().", ".$province.", ".$country."\n";
        	$msg .="Kodepos: ".$order->{"get_".$mode."_postcode"}()."\n";
        	if($mode=='shipping'){
        		$email = (isset($order->shipping['email']))?$order->shipping['email']:$order->get_billing_email();
        		$phone = (isset($order->shipping['phone']))?$order->shipping['phone']:$order->get_billing_phone();
        	}else{
        		$email = $order->get_billing_email();
        		$phone = $order->get_billing_phone();
        	}
        	$msg .="Email: ".$email."\n";
        	$msg .="No. Telepon: ".$phone."\n";
        	$msg .= "Catatan: ".$order->get_customer_note()."\n";
        	$msg .="\n";
        	$msg .="Thank you!\n\n";
        	$msg .= "(Waktu Server: ".date_i18n("j-F-Y - H:i",strtotime($order->get_date_created()->format('Y-m-d H:i:s'))).")";
        	$btn_text ='Kirim Order by WA ke: '.$vendor_name;
        	$html .=  '<a id="sendbtn" href="https://api.whatsapp.com/send?phone='.$wa.'&text='.rawurlencode($msg).'" target="_blank" class="wa-order-thankyou">'.$btn_text.'</a><br>';
		}

	}
	
	if(!empty($html)){
	    return '<div class="thankyoucustom_wrapper">
                    <h1 class="thankyoutitle">'.$judul.'</h1>
                    <p class="subtitle">'.$subtitle.'</p>'.$html.'</div>';
	}else{
	    return $title;    
	}
}

function wfcm_add_assets_wa_checkout(){
	wp_register_style( 'wa_checkout_style',  plugin_dir_url( __FILE__ ) . 'style.css' );
	wp_enqueue_style( 'wa_checkout_style' );
}
