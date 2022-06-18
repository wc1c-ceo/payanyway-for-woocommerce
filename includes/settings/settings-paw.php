<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings for PayAnyWay Gateway.
 */
$settings = array(
    'main' => array(
        'title' => __('PayAnyWay', 'woocommerce_gateway_payanyway'),
        'type' => 'title',
        'description' => __('Настройка приёма электронных платежей через PayAnyWay', 'woocommerce_gateway_payanyway'),
    ),
    'enabled' => array(
        'title' => __('Включить/Выключить', 'woocommerce_gateway_payanyway'),
        'type' => 'checkbox',
        'label' => __('Включен', 'woocommerce_gateway_payanyway'),
        'default' => 'yes',
    ),
    'title' => array(
        'title' => __('Название', 'woocommerce_gateway_payanyway'),
        'type' => 'text',
        'description' => __('Это название, которое пользователь видит во время проверки.', 'woocommerce_gateway_payanyway'),
        'default' => __('PayAnyWay', 'woocommerce_gateway_payanyway'),
    ),
    'MNT_ID' => array(
        'title' => __('Номер счёта', 'woocommerce_gateway_payanyway'),
        'type' => 'text',
        'description' => __('Пожалуйста введите Номер счёта.', 'woocommerce_gateway_payanyway'),
        'default' => '99999999',
    ),
    'MNT_DATAINTEGRITY_CODE' => array(
        'title' => __('Код проверки целостности данных', 'woocommerce_gateway_payanyway'),
        'type' => 'password',
        'description' => __('Пожалуйста введите Код проверки целостности данных, указанный в настройках расширенного счёта', 'woocommerce_gateway_payanyway'),
        'default' => '******',
    ),
    'MNT_TEST_MODE' => array(
        'title' => __('Тестовый режим', 'woocommerce_gateway_payanyway'),
        'type' => 'checkbox',
        'label' => __('Включен', 'woocommerce_gateway_payanyway'),
        'description' => __('В этом режиме плата за товар не снимается.', 'woocommerce_gateway_payanyway'),
        'default' => 'no',
    ),
    'autosubmitpawform' => array(
        'title' => __('Автоотправка', 'woocommerce_gateway_payanyway'),
        'type' => 'checkbox',
        'label' => __('Включить автоотправку формы оплаты', 'woocommerce_gateway_payanyway'),
        'description' => __('Покупатель, для совершения оплаты, будет автоматически перенаправлен на сайт PayAnyWay. Сможет выбрать желаемый способ оплаты', 'woocommerce_gateway_payanyway'),
        'default' => 'no',
    ),
    'iniframe' => array(
        'title' => __('Форма оплаты в iframe', 'woocommerce_gateway_payanyway'),
        'type' => 'checkbox',
        'label' => __('Встроить форму оплаты в страницу сайта.', 'woocommerce_gateway_payanyway'),
        'description' => __('Форма оплаты, предоставляемая PayAnyWay, будет встроена в страницу вашего сайта. Автоотправка невозможна. Покупатель сможет выбрать желаемый способ оплаты', 'woocommerce_gateway_payanyway'),
        'default' => 'no',
    ),
    'debug' => array(
        'title' => __('Отладка', 'woocommerce_gateway_payanyway'),
        'type' => 'checkbox',
        'label' => __('Включить логирование (<code>woocommerce/logs/payanyway.txt</code>)', 'woocommerce_gateway_payanyway'),
        'default' => 'no',
    ),
    'description' => array(
        'title' => __('Описание', 'woocommerce_gateway_payanyway'),
        'type' => 'textarea',
        'description' => __('Описанием метода оплаты которое клиент будет видеть на вашем сайте.', 'woocommerce_gateway_payanyway'),
        'default' => 'Оплата с помощью payanyway',
    ),
    'instructions' => array(
        'title' => __('Инструкции', 'woocommerce_gateway_payanyway'),
        'type' => 'textarea',
        'description' => __('Инструкции, которые будут добавлены на страницу благодарностей.', 'woocommerce_gateway_payanyway'),
        'default' => 'Оплата с помощью payanyway',
    ),
    'empty' => array(
        'title' => __('', 'woocommerce_gateway_payanyway'),
        'type' => 'title',
        'description' => __('', 'woocommerce_gateway_payanyway'),
    ),
);

return apply_filters('woocommerce_payanyway_settings', $settings);