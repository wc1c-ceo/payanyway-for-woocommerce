<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Plugin Name: PayAnyWay Payment Gateway
 * Description: Provides a PayAnyWay Payment Gateway.
 * Version: 1.2.19
 * Author: PayAnyWay
 */


/* Add a custom payment class to WC
  ------------------------------------------------------------ */
add_action('plugins_loaded', 'woocommerce_payanyway', 0);
function woocommerce_payanyway()
{
    if (!class_exists('WC_Payment_Gateway'))
        return; // if the WC payment gateway class is not available, do nothing
    if (class_exists('WC_Payanyway'))
        return;

    class WC_Payanyway extends WC_Payment_Gateway
    {
        private static $_domain = 'payanyway.ru';

        public function __construct()
        {
            $this->id = 'payanyway';
            $this->has_fields = false;

            // Load the settings
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->MNT_URL = self::$_domain;
            $this->MNT_ID = $this->get_option('MNT_ID');
            $this->MNT_DATAINTEGRITY_CODE = $this->get_option('MNT_DATAINTEGRITY_CODE');
            $this->MNT_TEST_MODE = $this->get_option('MNT_TEST_MODE');

            $this->title = $this->get_option('title');
            $this->autosubmitpawform = $this->get_option('autosubmitpawform');
            $this->iniframe = $this->get_option('iniframe');
            $this->debug = $this->get_option('debug');
            $this->description = $this->get_option('description');
            $this->instructions = $this->get_option('instructions');

            // Logs
            if ($this->debug == 'yes') {
                $this->log = new WC_Logger();
            }

            // Actions
            add_action('woocommerce_receipt_payanyway', array($this, 'receipt_page'));

            // Save options
            add_action('woocommerce_update_options_payment_gateways_payanyway', array($this, 'process_admin_options'));

            // Payment listener/API hook
            add_action('woocommerce_api_wc_payanyway', array($this, 'check_assistant_response'));

            if (!$this->is_valid_for_use()) {
                $this->enabled = false;
            }
        }

        /**
         * Check if this gateway is enabled and available in the user's country
         */
        function is_valid_for_use()
        {
            if (!in_array(get_option('woocommerce_currency'), array('RUB'))) {
                return false;
            }

            return true;
        }

        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis
         *
         * @since 0.1
         **/
        public function admin_options()
        {
            if ($this->is_valid_for_use()){
                $this->generate_settings_html();
            } else {
                ?>
                <div class="inline error">
                    <p>
                        <strong>
                        <?php _e('Шлюз отключен', 'woocommerce_gateway_payanyway'); ?>
                        </strong>:
                        <?php _e('PayAnyWay не поддерживает валюты вашего магазина.', 'woocommerce_gateway_payanyway'); ?>
                    </p>
                </div>
            <?php
            }
        } // End admin_options()

        /**
         * Initialise Gateway Settings Form Fields
         *
         * @access public
         * @return void
         */
        function init_form_fields()
        {
            $this->form_fields = include 'includes/settings/settings-paw.php';
        }

        /**
         * Дополнительная информация в форме выбора способа оплаты
         **/
        function payment_fields()
        {
            echo $this->get_description();
            if ( isset($_GET['pay_for_order']) && ! empty($_GET['key']) )
            {
                $order = wc_get_order( wc_get_order_id_by_order_key( wc_clean( $_GET['key'] ) ) );
                $this->receipt_page($order->get_id());
            }
        }

        /**
         * Process the payment and return the result
         **/
        function process_payment($order_id)
        {
            /** @var WC_Order $order */
            $order = new WC_Order($order_id);
            return array(
                'result' => 'success',
                'redirect' => add_query_arg(
                    'order', $order->get_id(),
                    add_query_arg(
                        'key', $order->get_order_key(),
                        $order->get_checkout_payment_url()
                    )
                ),
            );
        }

        private function cleanProductName($str)
        {
            return preg_replace('/[^0-9a-zA-Zа-яА-ЯёЁ\-\,\.\(\)\;\_\№\/\+\& ]/ui', '', htmlspecialchars_decode($str, ENT_QUOTES));
        }

        /**
         * Форма оплаты
         **/
        function receipt_page($order_id)
        {
            $order = new WC_Order($order_id);

            $amount = number_format($order->get_total(), 2, '.', '');
            $test_mode = ($this->MNT_TEST_MODE == 'yes') ? 1 : 0;
            $autosubmit_paw_form = ($this->autosubmitpawform == 'yes') ? 1 : 0;
            $in_iframe = ($this->iniframe == 'yes') ? 1 : 0;
            $currency = get_woocommerce_currency();
            if ($currency == 'RUR') $currency = 'RUB';
            $signature = md5($this->MNT_ID . $order_id . $amount . $currency . $test_mode . $this->MNT_DATAINTEGRITY_CODE);

            $wcKey = (isset($_GET['key'])) ? $_GET['key'] : '';

            $args = array(
                'MNT_ID' => $this->MNT_ID,
                'MNT_AMOUNT' => $amount,
                'MNT_TRANSACTION_ID' => $order_id,
                'MNT_TEST_MODE' => $test_mode,
                'MNT_CURRENCY_CODE' => $currency,
                'MNT_SIGNATURE' => $signature,
                'MNT_SUCCESS_URL' => get_site_url() . "/?wc-api=wc_payanyway&payanyway=success&key={$wcKey}&order_id={$order_id}",
                'MNT_FAIL_URL' => get_site_url() . "/?wc-api=wc_payanyway&payanyway=fail",
                'MNT_RETURN_URL' => get_site_url()
            );

            $form_fields = array();
            foreach ($args as $key => $value) {
                $form_fields[] = '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
            }

            wp_enqueue_style('paw_css_main', plugin_dir_url( __FILE__ ) . 'assets/css/paw-main.css', '', '');

            if ($in_iframe) {
                wp_enqueue_script('paw_js_ap', 'https://www.payanyway.ru/applepay/partnerwebmessaging.js');
                wp_enqueue_script('paw_js_iframe', plugin_dir_url(__FILE__) . 'assets/js/paw-iframe.js', '', '', true);
                $form_html = '<div id="pawiframewrapper" style="display: none; text-align: -webkit-center;">';
                $form_html .= http_build_query($args, '', '&amp;');
                $form_html .= '</div>';
            }
            else {
                wp_enqueue_script('paw_js_form', plugin_dir_url(__FILE__) . 'assets/js/paw-form.js', '', '', true);
                if ($autosubmit_paw_form) {
                    wp_enqueue_script('paw_js_autosubmit', plugin_dir_url(__FILE__) . 'assets/js/paw-autosubmit.js', '', '', true);
                }
                $form_html = '<div id="pawformwrapper" style="display: none; text-align: -webkit-center;">';
                $form_html .= implode("\n", $form_fields);
                $form_html .= '</div>';
            }

            echo $form_html;

        }

        /**
         * Check payanyway Pay URL validity
         **/
        function check_assistant_request_is_valid($posted)
        {
            if (isset($posted['MNT_ID']) && isset($posted['MNT_TRANSACTION_ID']) && isset($posted['MNT_OPERATION_ID'])
                && isset($posted['MNT_AMOUNT']) && isset($posted['MNT_CURRENCY_CODE']) && isset($posted['MNT_TEST_MODE'])
                && isset($posted['MNT_SIGNATURE'])
            ) {
                $signature = md5($posted['MNT_ID'] . $posted['MNT_TRANSACTION_ID'] . $posted['MNT_OPERATION_ID'] . $posted['MNT_AMOUNT'] . $posted['MNT_CURRENCY_CODE'] . $posted['MNT_TEST_MODE'] . $this->MNT_DATAINTEGRITY_CODE);
                if ($posted['MNT_SIGNATURE'] !== $signature) {
                    return false;
                }
            } else {
                return false;
            }

            return true;
        }

        /**
         * Check Response
         **/
        function check_assistant_response()
        {
            global $woocommerce;

            $_REQUEST = stripslashes_deep($_REQUEST);
            $MNT_TRANSACTION_ID = $_REQUEST['MNT_TRANSACTION_ID'];
            if (isset($_REQUEST['payanyway']) AND $_REQUEST['payanyway'] == 'callback') {
                @ob_clean();

                if ($this->check_assistant_request_is_valid($_REQUEST)) {
                    $order = new WC_Order($MNT_TRANSACTION_ID);
                    $items = $order->get_items();

                    // Payment completed
                    $order->add_order_note(__('Платеж успешно завершен.', 'woocommerce_gateway_payanyway'));
                    $order->update_status('processing', __('Платеж успешно оплачен', 'woocommerce_gateway_payanyway'));
                    $order->payment_complete();

                    //формирование xml ответа
                    header("Content-type: application/xml");
                    $resultCode = 200;
                    $signature = md5($resultCode . $_REQUEST['MNT_ID'] . $_REQUEST['MNT_TRANSACTION_ID'] . $this->MNT_DATAINTEGRITY_CODE);
                    $result = '<?xml version="1.0" encoding="UTF-8" ?>';
                    $result .= '<MNT_RESPONSE>';
                    $result .= '<MNT_ID>' . $_REQUEST['MNT_ID'] . '</MNT_ID>';
                    $result .= '<MNT_TRANSACTION_ID>' . $_REQUEST['MNT_TRANSACTION_ID'] . '</MNT_TRANSACTION_ID>';
                    $result .= '<MNT_RESULT_CODE>' . $resultCode . '</MNT_RESULT_CODE>';
                    $result .= '<MNT_SIGNATURE>' . $signature . '</MNT_SIGNATURE>';
                    $result .= '<MNT_ATTRIBUTES>';
                    $result .= '<ATTRIBUTE>';
                    $result .= '<KEY>cms</KEY>';
                    $result .= '<VALUE>wp</VALUE>';
                    $result .= '</ATTRIBUTE>';
                    $result .= '<ATTRIBUTE>';
                    $result .= '<KEY>cms_m</KEY>';
                    $result .= '<VALUE>wc</VALUE>';
                    $result .= '</ATTRIBUTE>';

                    // данные для кассы
                    $kassa_delivery = $order->get_total_shipping();

                    // добавить поля для кассы
                    $kassa_customer = method_exists($order, 'get_billing_email') ? $order->get_billing_email() : $order->billing_email;

                    $inventory = array();
                    foreach ($items AS $item) {
                        $itemName = (isset($item['name'])) ? $item['name'] : $item->get_name();
                        $itemPrice = $order->get_item_total($item);
                        $itemQuantity = (isset($item['item_meta']['_qty'][0])) ? $item['item_meta']['_qty'][0] : $item->get_quantity();
                        $inventory[] = array(
                                "name" => $this->cleanProductName($itemName),
                                "price" => $itemPrice,
                                "quantity" => $itemQuantity,
                                "vatTag" => 1105
                        );
                    }

                    if (count($inventory)) {
                        $kassa_inventory = json_encode($inventory);

                        if ($kassa_inventory) {
                            $result .= '<ATTRIBUTE>';
                            $result .= '<KEY>INVENTORY</KEY>';
                            $result .= '<VALUE>' . $kassa_inventory . '</VALUE>';
                            $result .= '</ATTRIBUTE>';
                        }

                        if ($kassa_customer) {
                            $result .= '<ATTRIBUTE>';
                            $result .= '<KEY>CUSTOMER</KEY>';
                            $result .= '<VALUE>' . $kassa_customer . '</VALUE>';
                            $result .= '</ATTRIBUTE>';
                        }

                        if ($kassa_delivery) {
                            $result .= '<ATTRIBUTE>';
                            $result .= '<KEY>DELIVERY</KEY>';
                            $result .= '<VALUE>' . $kassa_delivery . '</VALUE>';
                            $result .= '</ATTRIBUTE>';
                        }
                    }

                    $result .= '</MNT_ATTRIBUTES>';
                    $result .= '</MNT_RESPONSE>';
                    exit($result);

                } else {
                    die('FAIL');
                }
            } else if (isset($_REQUEST['payanyway']) AND $_REQUEST['payanyway'] == 'success') {
                $order = new WC_Order($MNT_TRANSACTION_ID);
                $woocommerce->cart->empty_cart();
                wp_redirect($this->get_return_url($order));
                exit;
            } else if (isset($_REQUEST['payanyway']) AND $_REQUEST['payanyway'] == 'fail') {
                $order = new WC_Order($MNT_TRANSACTION_ID);
                $order->update_status('failed', __('Платеж не оплачен', 'woocommerce_gateway_payanyway'));
                wp_redirect($order->get_cancel_order_url());
                exit;
            }

        }
    }

    /**
     * Add the gateway to WooCommerce
     **/
    function add_payanyway_gateway($methods)
    {
        $methods[] = 'WC_Payanyway';

        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_payanyway_gateway');
}

?>