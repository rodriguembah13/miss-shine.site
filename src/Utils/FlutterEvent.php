<?php


namespace App\Utils;


use Flutterwave\EventHandlerInterface;
//require_once $_SERVER['DOCUMENT_ROOT'] .'vendor/flutterwavedev/flutterwave-v3/library/raveEventHandlerInterface.php';
class FlutterEvent implements EventHandlerInterface{
    /**
     * This is called when the Rave class is initialized
     * @param $initializationData
     */
    function onInit($initializationData){
        // Save the transaction to your DB.
    }

    /**
     * This is called only when a transaction is successful
     * @param $transactionData
     */
    function onSuccessful($transactionData){
        // Get the transaction from your DB using the transaction reference (txref)
        // Check if you have previously given value for the transaction. If you have, redirect to your successpage else, continue
        // Comfirm that the transaction is successful
        // Confirm that the chargecode is 00 or 0
        // Confirm that the currency on your db transaction is equal to the returned currency
        // Confirm that the db transaction amount is equal to the returned amount
        // Update the db transaction record (includeing parameters that didn't exist before the transaction is completed. for audit purpose)
        // Give value for the transaction
        // Update the transaction to note that you have given value for the transaction
        // You can also redirect to your success page from here
        if($transactionData->chargecode === '00' || $transactionData->chargecode === '0'){
            if($transactionData->currency == $_SESSION['currency'] && $transactionData->amount == $_SESSION['amount']){

                if($_SESSION['publicKey']){
                    header('Location: '.getURL($_SESSION['successurl'], array('event' => 'successful')));
                    $_SESSION = array();
                    session_destroy();
                }
            }else{
                if($_SESSION['publicKey']){
                    header('Location: '.getURL($_SESSION['failureurl'], array('event' => 'suspicious')));
                    $_SESSION = array();
                    session_destroy();
                }
            }
        }else{
            $this->onFailure($transactionData);
        }
    }

    /**
     * This is called only when a transaction failed
     * @param $transactionData
     */
    function onFailure($transactionData){
        // Get the transaction from your DB using the transaction reference (txref)
        // Update the db transaction record (includeing parameters that didn't exist before the transaction is completed. for audit purpose)
        // You can also redirect to your failure page from here
        if($_SESSION['publicKey']){
            header('Location: '.getURL($_SESSION['failureurl'], array('event' => 'failed')));
            $_SESSION = array();
            session_destroy();
        }
    }

    /**
     * This is called when a transaction is requeryed from the payment gateway
     * @param $transactionReference
     */
    function onRequery($transactionReference){
        // Do something, anything!
    }

    /**
     * This is called a transaction requery returns with an error
     * @param $requeryResponse
     */
    function onRequeryError($requeryResponse){
        // Do something, anything!
    }

    /**
     * This is called when a transaction is canceled by the user
     * @param $transactionReference
     */
    function onCancel($transactionReference){
        // Do something, anything!
        // Note: Somethings a payment can be successful, before a user clicks the cancel button so proceed with caution
        if($_SESSION['publicKey']){
            header('Location: '.getURL($_SESSION['failureurl'], array('event' => 'canceled')));
            $_SESSION = array();
            session_destroy();
        }
    }

    /**
     * This is called when a transaction doesn't return with a success or a failure response. This can be a timedout transaction on the Rave server or an abandoned transaction by the customer.
     * @param $transactionReference
     * @param $data
     */
    function onTimeout($transactionReference, $data){
        // Get the transaction from your DB using the transaction reference (txref)
        // Queue it for requery. Preferably using a queue system. The requery should be about 15 minutes after.
        // Ask the customer to contact your support and you should escalate this issue to the flutterwave support team. Send this as an email and as a notification on the page. just incase the page timesout or disconnects
        if($_SESSION['publicKey']){
            header('Location: '.getURL($_SESSION['failureurl'], array('event' => 'timedout')));
            $_SESSION = array();
            session_destroy();
        }
    }
}
