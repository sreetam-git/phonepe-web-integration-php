<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Phonepe extends CI_Controller {
    
    private $merchantId;
    private $saltKey;
    private $saltIndex;
    private $env = "UAT"; // Change to PRODUCTION for live environment
    
    public function __construct() {
        parent::__construct();
        $this->load->helper('url');
        $this->load->library('session');
        
        // Initialize credentials
        $this->merchantId = "TEST-M22HJF0MFELD7_25051";  // Client ID
        $this->saltKey = "NDFkYmZhNTMtZDMxNS00NGFjLTlmOGEtNWZmZDhhNTRlZjJi";  // Client Secret
        $this->saltIndex = "1";  // Client Version
        
        // Validate credentials
        if (empty($this->merchantId)) {
            log_message('error', 'PhonePe: Merchant ID not configured');
        }
        if (empty($this->saltKey)) {
            log_message('error', 'PhonePe: Salt Key not configured');
        }
        if (empty($this->saltIndex)) {
            log_message('error', 'PhonePe: Salt Index not configured');
        }

        // Debug log the configuration
        log_message('debug', 'PhonePe Configuration: ' . json_encode([
            'merchantId' => $this->merchantId,
            'saltIndex' => $this->saltIndex,
            'env' => $this->env
        ]));
    }

    // Initiate payment
    public function initiate_payment() {
        try {
            // Generate unique transaction ID
            $merchantTransactionId = uniqid('TXN_');
            
            // Amount in paise (multiply by 100)
            $amount = 100 * $this->input->post('amount');
            
            // Prepare payment data
            $payload = [
                "merchantId" => $this->merchantId,
                "merchantTransactionId" => $merchantTransactionId,
                "merchantUserId" => "MUID_" . time(),
                "amount" => $amount,
                "callbackUrl" => base_url('phonepe/callback'),
                "redirectUrl" => base_url('phonepe/redirect'),
                "redirectMode" => "REDIRECT",
                "mobileNumber" => $this->input->post('mobile'),
                "paymentInstrument" => [
                    "type" => "PAY_PAGE"
                ]
            ];

            // Convert payload to base64
            $base64Payload = base64_encode(json_encode($payload));
            
            // Generate checksum
            $checksum = hash('sha256', $base64Payload . "/v4/debit" . $this->saltKey) . "###" . $this->saltIndex;
            
            // API endpoint
            $apiEndpoint = ($this->env == "UAT") 
                ? "https://api-preprod.phonepe.com/apis/merchant-simulator/pg/v1/pay"
                : "https://api.phonepe.com/apis/hermes/pg/v1/pay";
            
            // Make cURL request
            $ch = curl_init($apiEndpoint);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                "request" => $base64Payload
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-VERIFY: ' . $checksum
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            $response = curl_exec($ch);
            
            if(curl_errno($ch)){
                throw new Exception('Curl error: ' . curl_error($ch));
            }
            
            curl_close($ch);
            
            $responseData = json_decode($response);
            
            // Debug logging
            log_message('debug', 'PhonePe Request: ' . json_encode($payload));
            log_message('debug', 'PhonePe Response: ' . $response);
            
            if ($responseData->success) {
                // Store transaction details in session for verification
                $this->session->set_userdata('phonepe_transaction', [
                    'merchantTransactionId' => $merchantTransactionId,
                    'amount' => $amount
                ]);
                
                // Redirect to PhonePe payment page
                redirect($responseData->data->instrumentResponse->redirectInfo->url);
            } else {
                // Log the error
                log_message('error', 'PhonePe Error: ' . $responseData->message . ' (Code: ' . $responseData->code . ')');
                
                // Handle error
                $this->session->set_flashdata('error', 'Payment initiation failed: ' . $responseData->message);
                redirect('payment/error');
            }
        } catch (Exception $e) {
            log_message('error', 'PhonePe Exception: ' . $e->getMessage());
            $this->session->set_flashdata('error', 'System error occurred. Please try again later.');
            redirect('payment/error');
        }
    }

    // Callback function
    public function callback() {
        $response = file_get_contents('php://input');
        $xVerify = $_SERVER['HTTP_X_VERIFY'] ?? '';
        
        // Verify callback signature
        $computedSignature = hash('sha256', $response . $this->saltKey) . "###" . $this->saltIndex;
        
        if ($computedSignature === $xVerify) {
            $responseData = json_decode($response, true);
            
            // Get stored transaction details
            $storedTransaction = $this->session->userdata('phonepe_transaction');
            
            if ($responseData['success'] && 
                $responseData['data']['merchantTransactionId'] === $storedTransaction['merchantTransactionId'] &&
                $responseData['data']['amount'] === $storedTransaction['amount']) {
                
                // Payment successful
                // Update your database here
                
                $this->session->set_flashdata('success', 'Payment successful');
                redirect('payment/success');
            } else {
                // Payment failed
                $this->session->set_flashdata('error', 'Payment failed');
                redirect('payment/error');
            }
        } else {
            // Invalid signature
            $this->session->set_flashdata('error', 'Invalid payment response');
            redirect('payment/error');
        }
    }

    // Redirect function
    public function redirect() {
        // Handle redirect from PhonePe
        // You can add additional logic here if needed
        $status = $this->input->get('status');
        
        if ($status === 'SUCCESS') {
            redirect('payment/success');
        } else {
            redirect('payment/error');
        }
    }

    // Check payment status
    public function check_status($merchantTransactionId) {
        $apiEndpoint = ($this->env == "UAT")
            ? "https://api-preprod.phonepe.com/apis/merchant-simulator/pg/v1/status/"
            : "https://api.phonepe.com/apis/hermes/pg/v1/status/";
            
        $apiEndpoint .= $this->merchantId . "/" . $merchantTransactionId;
        
        // Generate checksum
        $checksum = hash('sha256', "/v4/status/" . $this->merchantId . "/" . $merchantTransactionId . $this->saltKey) . "###" . $this->saltIndex;
        
        $ch = curl_init($apiEndpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-VERIFY: ' . $checksum,
            'X-MERCHANT-ID: ' . $this->merchantId
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
} 