<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Plugin Name: PayAnyWay Payment Gateway
 * Description: Provides a PayanyWay Payment Gateway.
 * Version: 1.2.15
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
        public function __construct()
        {
            $plugin_dir = plugin_dir_url(__FILE__);

            global $woocommerce;

            $this->id = 'payanyway';
            $this->icon = apply_filters('woocommerce_payanyway_icon', '' . $plugin_dir . 'payanyway.png');
            $this->has_fields = false;

            // Load the settings
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title = $this->get_option('title');
            $this->MNT_URL = $this->get_option('MNT_URL');
            $this->MNT_ID = $this->get_option('MNT_ID');
            $this->MNT_DATAINTEGRITY_CODE = $this->get_option('MNT_DATAINTEGRITY_CODE');
            $this->MNT_TEST_MODE = $this->get_option('MNT_TEST_MODE');
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
            ?>
            <h3><?php _e('PayAnyWay', 'woocommerce'); ?></h3>
            <p><?php _e('Настройка приема электронных платежей через PayAnyWay.', 'woocommerce'); ?></p>

            <?php if ($this->is_valid_for_use()) : ?>

            <table class="form-table">

                <?php
                // Generate the HTML For the settings form.
                $this->generate_settings_html();
                ?>
            </table><!--/.form-table-->

        <?php else : ?>
            <div class="inline error"><p>
                    <strong><?php _e('Шлюз отключен', 'woocommerce'); ?></strong>: <?php _e('PayAnyWay не поддерживает валюты Вашего магазина.', 'woocommerce'); ?>
                </p></div>
            <?php
        endif;

        } // End admin_options()

        /**
         * Initialise Gateway Settings Form Fields
         *
         * @access public
         * @return void
         */
        function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Включить/Выключить', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Включен', 'woocommerce'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Название', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Это название, которое пользователь видит во время проверки.', 'woocommerce'),
                    'default' => __('PayAnyWay', 'woocommerce')
                ),
                'MNT_ID' => array(
                    'title' => __('Номер счета', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Пожалуйста введите Номер счета.<br/><b>Внимание!</b>Номер расширенного счета на demo.moneta.ru и в рабочем аккаунте PayAnyWay отличаются.', 'woocommerce'),
                    'default' => '99999999'
                ),
                'MNT_DATAINTEGRITY_CODE' => array(
                    'title' => __('Код проверки целостности данных', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Пожалуйста введите Код проверки целостности данных, указанный в настройках расширенного счета', 'woocommerce'),
                    'default' => '******'
                ),
                'MNT_URL' => array(
                    'title' => __('URL сервера оплаты', 'woocommerce'),
                    'type' => 'select',
                    'options' => array(
                        'demo.moneta.ru' => 'demo.moneta.ru',
                        'www.payanyway.ru' => 'www.payanyway.ru'
                    ),
                    'description' => __('Пожалуйста выберите URL сервера оплаты.', 'woocommerce'),
                    'default' => ''
                ),
                'MNT_TEST_MODE' => array(
                    'title' => __('Тестовый режим', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Включен', 'woocommerce'),
                    'description' => __('В этом режиме плата за товар не снимается.', 'woocommerce'),
                    'default' => 'no'
                ),
                'autosubmitpawform' => array(
                    'title' => __('Автоотправка', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Включить автоотправку формы оплаты', 'woocommerce'),
                    'default' => 'no'
                ),
                'iniframe' => array(
                    'title' => __('Форма оплаты в iframe', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Встроить форму оплаты в страницу сайта.', 'woocommerce'),
                    'description' => __('Форма оплаты, предоставляемая платёжной системой, будет встроена в страницу Вашего сайта.', 'woocommerce'),
                    'default' => 'no',
                ),
                'debug' => array(
                    'title' => __('Debug', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Включить логирование (<code>woocommerce/logs/payanyway.txt</code>)', 'woocommerce'),
                    'default' => 'no'
                ),
                'description' => array(
                    'title' => __('Description', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('Описанием метода оплаты которое клиент будет видеть на вашем сайте.', 'woocommerce'),
                    'default' => 'Оплата с помощью payanyway.'
                ),
                'instructions' => array(
                    'title' => __('Instructions', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('Инструкции, которые будут добавлены на страницу благодарностей.', 'woocommerce'),
                    'default' => 'Оплата с помощью payanyway.'
                ),

            );

        }

        /**
         * Дополнительная информация в форме выбора способа оплаты
         **/
        function payment_fields()
        {
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
                'redirect' => add_query_arg('order', $order->get_id(), add_query_arg('key', $order->get_order_key(), get_permalink(woocommerce_get_page_id('pay'))))
            );
        }

        function cleanProductName($value)
        {
            $result = preg_replace('/[^0-9a-zA-Zа-яА-Я ]/ui', '', htmlspecialchars_decode($value));
            $result = trim(mb_substr($result, 0, 12));
            return $result;
        }

        /**
         * Форма оплаты
         **/
        function receipt_page($order_id)
        {
            $order = new WC_Order($order_id);

            $amount = number_format($order->get_total(), 2, '.', '');
            $test_mode = ($this->MNT_TEST_MODE == 'yes') ? 1 : 0;
            $autosubmitpawform = ($this->autosubmitpawform == 'yes') ? 1 : 0;
            $iniframe = ($this->iniframe == 'yes') ? 1 : 0;
            $currency = get_woocommerce_currency();
            if ($currency == 'RUR') $currency = 'RUB';
            $signature = md5($this->MNT_ID . $order_id . $amount . $currency . $test_mode . htmlspecialchars_decode($this->MNT_DATAINTEGRITY_CODE));

            $args = array(
                'MNT_ID' => $this->MNT_ID,
                'MNT_AMOUNT' => $amount,
                'MNT_TRANSACTION_ID' => $order_id,
                'MNT_TEST_MODE' => $test_mode,
                'MNT_CURRENCY_CODE' => $currency,
                'MNT_SIGNATURE' => $signature
            );

            $args_array = array();

            foreach ($args as $key => $value) {
                $args_array[] = '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
            }

            if($iniframe) 
            {
                $annotation =   '<p>' . __('Спасибо за Ваш заказ, пожалуйста, заполните форму ниже, чтобы сделать платёж.', 'woocommerce') . '</p>';
                $form_html =    '<div class="payanyway_wrapper">
                                    <iframe src="' . esc_url("https://" . $this->MNT_URL . "/assistant.widget?" . http_build_query($args, '', '&amp;')) . '"
                                    id="payanyway_payment_form" name="paymentform" frameborder="0" style="width: 90%; height: -webkit-fill-available; min-height: 550px; margin-left: -50px; margin-top: -22px;">
                                    </iframe>
                                </div>';
            } else {
                $annotation =   '<p>' . __('Спасибо за Ваш заказ, пожалуйста, нажмите кнопку ниже, чтобы сделать платёж.', 'woocommerce') . '</p>';
            	if ( isset($_GET['pay_for_order']) && ! empty($_GET['key']) )
            	{
	                $form_html = '<form></form><form action="'.esc_url("https://" . $this->MNT_URL . "/assistant.htm") . '" method="POST" id="payanyway_payment_form" name="paymentform">'."\n".
                        implode("\n", $args_array).
                        '<input type="submit" class="button alt" style="display: none" id="submit_payanyway_payment_form" value="' . __('Оплатить', 'woocommerce') . '" />'."\n" .
                        '</form>'."\n".
                        '<script type="text/javascript">'."\n".
                        'jQuery(function() {'."\n".
                        '    jQuery("#order_review").submit(function(ev) {'."\n".
                        '        if (jQuery("#payment_method_payanyway").prop("checked")) {'."\n".
                        '            ev.preventDefault();'."\n".
                        '            jQuery("#submit_payanyway_payment_form").click();'."\n".
                        '        }'."\n".
                        '    });'."\n".
                        '});</script>';
            	} else {
	                $form_html =    '<form action="' . esc_url("https://" . $this->MNT_URL . "/assistant.htm") . '" method="POST" id="payanyway_payment_form" name="paymentform">' . "\n" .
	                                    implode("\n", $args_array) .
	                                    '<input type="submit" class="button alt" id="submit_payanyway_payment_form" value="' . __('Оплатить', 'woocommerce') . '" /> <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __('Отказаться от оплаты и вернуться в корзину', 'woocommerce') . '</a>' . "\n" .
	                                '</form>';
	                if ($autosubmitpawform) {
    	                $form_html .= '<script type="text/javascript">document.paymentform.submit();</script>';
	                }
                }
            }
            echo $annotation.$form_html;
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
                $signature = md5($posted['MNT_ID'] . $posted['MNT_TRANSACTION_ID'] . $posted['MNT_OPERATION_ID'] . $posted['MNT_AMOUNT'] . $posted['MNT_CURRENCY_CODE'] . $posted['MNT_TEST_MODE'] . htmlspecialchars_decode($this->MNT_DATAINTEGRITY_CODE));
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

                    // Check order not already completed
                    /*
                    if ($order->status == 'completed') {
                        die('FAIL');
                    }
                    */

                    // Payment completed
                    $order->add_order_note(__('Платеж успешно завершен.', 'woocommerce'));
                    $order->update_status('processing', __('Платеж успешно оплачен', 'woocommerce'));
                    $order->payment_complete();

                    // данные для кассы
                    $kassa_inventory = null;
                    $kassa_customer = null;
                    $kassa_delivery = $order->get_total_shipping();

                    // добавить поля для кассы
                    $kassa_customer = method_exists($order, 'get_billing_email') ? $order->get_billing_email() : $order->billing_email;

                    $inventory = array();
                    foreach ($items AS $item) {
                        $itemName = (isset($item['name'])) ? $item['name'] : $item->get_name();
                        $itemPrice = $order->get_item_total($item);
                        $itemQuantity = (isset($item['item_meta']['_qty'][0])) ? $item['item_meta']['_qty'][0] : $item->get_quantity();
                        $inventory[] = array("name" => trim(preg_replace("/&?[a-z0-9]+;/i", "", htmlspecialchars($itemName))), "price" => $itemPrice, "quantity" => $itemQuantity, "vatTag" => 1105);
                    }

                    if (count($inventory)) {
                        $kassa_inventory = json_encode($inventory);
                        // сформировать xml ответ
                        header("Content-type: application/xml");
                        $resultCode = 200;
                        $signature = md5($resultCode . $_REQUEST['MNT_ID'] . $_REQUEST['MNT_TRANSACTION_ID'] . htmlspecialchars_decode($this->MNT_DATAINTEGRITY_CODE));
                        $result = '<?xml version="1.0" encoding="UTF-8" ?>';
                        $result .= '<MNT_RESPONSE>';
                        $result .= '<MNT_ID>' . $_REQUEST['MNT_ID'] . '</MNT_ID>';
                        $result .= '<MNT_TRANSACTION_ID>' . $_REQUEST['MNT_TRANSACTION_ID'] . '</MNT_TRANSACTION_ID>';
                        $result .= '<MNT_RESULT_CODE>' . $resultCode . '</MNT_RESULT_CODE>';
                        $result .= '<MNT_SIGNATURE>' . $signature . '</MNT_SIGNATURE>';

                        if ($kassa_inventory || $kassa_customer || $kassa_delivery) {
                            $result .= '<MNT_ATTRIBUTES>';
                        }

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

                        if ($kassa_inventory || $kassa_customer || $kassa_delivery) {
                            $result .= '</MNT_ATTRIBUTES>';
                        }

                        $result .= '</MNT_RESPONSE>';

                        echo $result;

                    }
                    else {
                        echo 'SUCCESS';
                    }

                    exit;

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
                $order->update_status('failed', __('Платеж не оплачен', 'woocommerce'));
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