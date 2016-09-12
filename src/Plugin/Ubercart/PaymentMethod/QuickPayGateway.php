<?php

namespace Drupal\uc_quickpay\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;
use Drupal\uc_payment\OffsitePaymentMethodPluginInterface;

/**
 * QuickPay Ubercart gateway payment method.
 *
 *
 * @UbercartPaymentMethod(
 *   id = "quickpay_gateway",
 *   name = @Translation("QuickPay"),
 * )
 */
class QuickPayGateway extends PaymentMethodPluginBase implements OffsitePaymentMethodPluginInterface{
    /**
      * {@inheritdoc}
    */
    public function defaultConfiguration() {
        return parent::defaultConfiguration() + [
            'testmode'  => TRUE,
            'api' => [
                'merchant_id'  => '',
                'private_key'  => '',
                'agreement_id' => '',
                'api_key'      => '',
                'language'     => '',
                'currency'     => '',
                ],
            'callbacks' => [
                'continue_url' => '',
                'cancel_url'   => '',
                ]
        ];
    }
    /**
      * {@inheritdoc}
    */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildConfigurationForm($form, $form_state);

        $form['api'] = array(
            '#type' => 'details',
            '#title' => $this->t('API credentials'),
            '#description' => $this->t('@link for information on obtaining credentials. You need to acquire an API Signature. If you have already requested API credentials, you can review your settings under the API Access section of your QuickPayGateway profile.', ['@link' => Link::fromTextAndUrl($this->t('Click here'), Url::fromUri('http://tech.quickpay.net/api/'))->toString()]),
            '#open' => TRUE,
        );

        $form['api']['merchant_id'] = array(
            '#type' => 'textfield',
            '#title' => t('Merchant ID'),
            '#default_value' => $this->configuration['api']['merchant_id'],
            '#description' => t('The Merchant ID as shown in the QuickPay admin.'),
        );

        $form['api']['private_key'] = array(
            '#type' => 'textfield',
            '#title' => t('Private key'),
            '#default_value' => $this->configuration['api']['private_key'],
            '#description' => t('Your private key.'),
        );

        $form['api']['agreement_id'] = array(
            '#type' => 'textfield',
            '#title' => t('Agreement ID'),
            '#default_value' => $this->configuration['api']['agreement_id'],
            '#description' => t('This is the Payment Window Agreement ID.'),
        );

        $form['api']['api_key'] = array(
            '#type' => 'textfield',
            '#title' => t('API key'),
            '#default_value' => $this->configuration['api']['api_key'],
            '#description' => t('This is the Payment Window API key.'),
        );

        $form['language'] = array(
            '#type' => 'select',
            '#options' => array(
                    'da' => 'Danish',
                    'de' => 'German',
                    'en' => 'English',
                    'fr' => 'French',
                    'it' => 'Italian',
                    'no' => 'Norwegian',
                    'nl' => 'Dutch',
                    'pl' => 'Polish',
                    'se' => 'Swedish',
                ),
            '#title' => t('Select Language'),
            '#default_value' => $this->configuration['language'],
            '#description' => t('The language for the credit card form.'),
        );

        $form['currency'] = array(
            '#type' => 'select',
            '#options' => array(
                    'DKK' => 'DKK',
                    'EUR' => 'EUR',
                    'USD' => 'USD',
                    'SEK' => 'SEK',
                    'NOK' => 'NOK',
                    'GBP' => 'GBP',
                ),
            '#title' => t('Select Currency'),
            '#default_value' => $this->configuration['currency'],
            '#description' => t('Your currency.'),
        );

        $form['testmode'] = array(
            '#type' => 'checkbox',
            '#title' => t('Test mode'),
            '#description' => 'When active, transactions will be run in test mode, even if the QuickPay account is in production mode. Order ids will get a T appended.',
            '#default_value' => $this->configuration['testmode'],
        );

        $form['callbacks'] = array(
            '#type' => 'details',
            '#title' => $this->t('CALLBACKS'),
            '#description' => $this->t('Quickpay callback urls.'),
            '#open' => TRUE,
        );

        $form['callbacks']['continue_url'] = array(
            '#type' => 'textfield',
            '#title' => t('Continue URL'),
            '#default_value' => $this->configuration['callbacks']['continue_url'],
            '#description' => t('After a successful transaction.'),
        );

        $form['callbacks']['cancel_url'] = array(
            '#type' => 'textfield',
            '#title' => t('Cancel URL'),
            '#default_value' => $this->configuration['callbacks']['cancel_url'],
            '#description' => t('If the user cancels the QuickPay transaction.'),    
        );

        return $form;
    }

    public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
        $elements = ['merchant_id', 'private_key', 'agreement_id', 'api_key'];

        foreach ($elements as $element_name) {
            $raw_key = $form_state->getValue(['settings', 'api', $element_name]);
            $sanitized_key = $this->trimKey($raw_key);
            $form_state->setValue(['settings', $element_name], $sanitized_key);
            if (!$this->validateKey($form_state->getValue(['settings', $element_name]))) {
                $form_state->setError($form[$element_name], t('@name does not appear to be a valid QuickPay key', array('@name' => $element_name)));
            }
        }

        parent::validateConfigurationForm($form, $form_state);
    }

    protected function trimKey($key) {
        $key = trim($key);
        $key = \Drupal\Component\Utility\Html::escape($key);
        return $key;
    }
    /**
        * Validate QuickPay key
        *
        * @param $key
        * @return boolean
    */
    static public function validateKey($key) {
        $valid = preg_match('/^[a-zA-Z0-9_]+$/', $key);
        return $valid;
    }

    /**
        * {@inheritdoc}
    */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
        foreach (['merchant_id', 'private_key', 'agreement_id', 'api_key'] as $item) {
            $this->configuration['api'][$item] = $form_state->getValue(['settings', 'api', $item]);
        }
        $this->configuration['language'] = $form_state->getValue('language');
        $this->configuration['currency'] = $form_state->getValue('currency');
        $this->configuration['callbacks']['continue_url'] = $form_state->getValue(['settings', 'callbacks', 'continue_url']);
        $this->configuration['callbacks']['cancel_url'] = $form_state->getValue(['settings', 'callbacks', 'cancel_url']);

        return parent::submitConfigurationForm($form, $form_state);
    }

    public function cartProcess(OrderInterface $order, array $form, FormStateInterface $form_state) {
        $params = array(
            "version"      => QUICKPAY_VERSION,
            "merchant_id"  => $this->configuration['api']['merchant_id'],
            "agreement_id" => $this->configuration['api']['agreement_id'],
            "order_id"     => $order->id(),
            "amount"       => $order->getTotal(),
            "currency"     => $this->configuration['currency'],
            "continueurl" => $this->configuration['callbacks']['continue_url'],
            "cancelurl"   => $this->configuration['callbacks']['cancel_url'],
        );

        $params["checksum"] = $this->getChecksum($params, $this->configuration['api_key']);
        
        if (!empty($params["checksum"])) {
            \Drupal::service('user.private_tempstore')->get('uc_quickpay')->set('uc_quickpay_token', $params["checksum"]);
        }
        return parent::cartProcess($order, $form, $form_state); // TODO: Change the autogenerated stub
    }

    /**
        * {@inheritdoc}
    */
    /**
    * {@inheritdoc}
    */
    public function buildRedirectForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL) {
        global $base_url;

        $plugin = \Drupal::service('plugin.manager.uc_payment.method')->createFromOrder($order);
        
        $tokenn = \Drupal::service('user.private_tempstore')->get('uc_quickpay')->get('uc_quickpay_token');
        
        if($plugin->getPluginId() == 'quickpay_gateway'){

            $shipping = 0;
            foreach ($order->line_items as $item) {
                if ($item['type'] == 'shipping') {
                    $shipping += $item['amount'];
                }
            }

            $tax = 0;
            if (\Drupal::moduleHandler()->moduleExists('uc_tax')) {
                foreach (uc_tax_calculate($order) as $tax_item) {
                    $tax += $tax_item->amount;
                }
            }

            $form['#action'] = 'https://payment.quickpay.net';
            
            $address = $order->getAddress('billing');
            $country = $address->country;
            
            $data = array(
                // Display information.
                'version' => QUICKPAY_VERSION,
                'merchant_id' => $this->configuration['api']['merchant_id'],
                'agreement_id' => $this->configuration['api']['agreement_id'],
                'order_id' => $order->id(),
                'currency' => $order->getCurrency(),
                'amount' => $order->getTotal(),

                'continueurl' => $base_url . '/'. $this->configuration['callbacks']['continue_url'],
                'cancel_url' => $base_url . '/'. $this->configuration['callbacks']['cancel_url'],

                'language' => $this->configuration['language'],

                // Prepopulating forms/address overriding.
                'address1' => substr($address->street1, 0, 100),
                'address2' => substr($address->street2, 0, 100),
                'city' => substr($address->city, 0, 40),
                'country' => $country,
                'email' => $order->getEmail(),
                'first_name' => substr($address->first_name, 0, 32),
                'last_name' => substr($address->last_name, 0, 64),
                'state' => $address->zone,
                'zip' => $address->postal_code,

                'checksum' => $tokenn,
            );
            
            foreach ($data as $name => $value) {
                if (!empty($value)) {
                    $form[$name] = array('#type' => 'hidden', '#value' => $value);
                }
            }
                    
            // $form['version'] = array(
            //     '#type' => 'hidden',
            //     '#default_value' => QUICKPAY_VERSION,
            //     '#attributes' => array(
            //         'id' => 'order_review_version',
            //     ),
            // );

            // $form['merchant_id'] = array(
            //     '#type' => 'hidden',
            //     '#default_value' => $this->configuration['api']['merchant_id'],
            //     '#attributes' => array(
            //         'id' => 'order_review_merchant_id',
            //     ),
            // );

            // $form['agreement_id'] = array(
            //     '#type' => 'hidden',
            //     '#default_value' => $this->configuration['api']['agreement_id'],
            //     '#attributes' => array(
            //         'id' => 'order_review_agreement_id',
            //     ),
            // );

            // $form['order_id'] = array(
            //     '#type' => 'hidden',
            //     '#default_value' => $order->id(),
            //     '#attributes' => array(
            //         'id' => 'order_review_order_id',
            //     ),
            // );

            // $form['amount'] = array(
            //     '#type' => 'hidden',
            //     '#default_value' => $order->getTotal(),
            //     '#attributes' => array(
            //         'id' => 'order_review_amount',
            //     ),
            // );
            // if($this->configuration['currency'] == $order->getCurrency()){
            //     $form['currency'] = array(
            //         '#type' => 'hidden',
            //         '#default_value' => $order->getCurrency(),
            //         '#attributes' => array(
            //             'id' => 'order_review_currency',
            //         ),
            //     );
            // }

            // if($this->configuration['callbacks']['continue_url']){
            //     $form['continueurl'] = array(
            //         '#type' => 'hidden',
            //         '#default_value' => $base_url . '/'. $this->configuration['callbacks']['continue_url'],
            //         '#attributes' => array(
            //             'id' => 'order_review_continue_url',
            //         ),
            //     );
            // }

            // if($this->configuration['callbacks']['cancel_url']){
            //     $form['cancel_url'] = array(
            //         '#type' => 'hidden',
            //         '#default_value' => $base_url . '/'. $this->configuration['callbacks']['cancel_url'],
            //         '#attributes' => array(
            //             'id' => 'order_review_callbackurl',
            //         ),
            //     );
            // }

            // $form['checksum'] = array(
            //     '#type' => 'hidden',
            //     '#default_value' => $tokenn,
            //     '#attributes' => array(
            //         'id' => 'order_review_checksum',
            //     ),
            // );
            
            $form['actions'] = array('#type' => 'actions');
            
            $form['actions']['submit'] = array(
              '#type' => 'submit',
              '#value' => $this->t('Quickpay Order'),
            );

            return $form;
        }
    }
    /**
      * Utility function: Load Paytrek API
      *
      * @return bool
    */
    public function prepareApi() {
        // Not clear that this is useful since payment config form forces at least some config
        if (!_uc_quickpay_check_api_keys($this->getConfiguration())) {
            \Drupal::logger('uc_paytrek')->error('Paytrek API keys are not configured. Payments cannot be made without them.', array());
            return FALSE;
        }

        $private_key = $this->configuration['merchant_id'];
        // try {
        //     \Paytrek\Paytrek::setApiKey($private_key);
        // } catch (Exception $e) {
        //     \Drupal::logger('uc_Paytrek')->notice('Error setting the Paytrek API Key. Payments will not be processed: %error', array('%error' => $e->getMessage()));
        // }
        return TRUE;
    }

    // function review_form_with_quickpay(OrderInterface $order, array $form, FormStateInterface $form_state){
    //     var_dump('test');
    //     exit;
    //     $form['quickpay_token'] = array(
    //         '#type' => 'hidden',
    //         '#default_value' => 'default',
    //         '#attributes' => array(
    //             'id' => 'edit-panes-payment-details-quickpay-token',
    //         ),
    //     );   
    //     return $form;
    // }

    /**
      * Calculate the hash for the request.
      *
      * @param array $data
      *   The data to POST to Quickpay.
      *
      * @return string
      *   The checksum.
      *
      * @see http://tech.quickpay.net/payments/hosted/#checksum
    */
    protected function getChecksum(array $data, $api_key) {
        $flattened_params = $this->flattenParams($data);
        ksort($flattened_params);
        $base = implode(" ", $flattened_params);
        return hash_hmac("sha256", $base, $api_key);
    }

    /**
      * Flatten request parameter array.
    */
    protected function flattenParams($obj, $result = array(), $path = array()) {
        if (is_array($obj)) {
            foreach ($obj as $k => $v) {
                $result = array_merge($result, $this->flattenParams($v, $result, array_merge($path, array($k))));
            }
        }
        else {
            $result[implode("", array_map(function($param) {
                return "[{$param}]";
            }, $path))] = $obj;
        }

        return $result;
    }

}