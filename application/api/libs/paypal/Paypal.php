<?php
namespace app\api\libs\paypal;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Refund;
use PayPal\Api\Sale;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

class Paypal{

    private $client_id;
    private $secret;
    private $online;

    public function __construct()
    {
        $config = db("paypal_config")->where("id","=",1)->find();
        if(empty($config)){
            return ['status'=>false,'msg'=>"暂未配置paypal支付信息"];
        }
        $this->client_id = $config['client_id'];
        $this->secret = $config['secret'];
        $this->online = $config['online'];
    }

    /**
     * 支付
     * @param $type 1 沙箱环境 2正式环境
     * @param $body 支付名称
     * @param $money 支付金额
     * @param $notify_url h5支付成功地址
     * @param $cancel_url h5支付失败地址
     * @return array
     */
    public function createPayPal($type,$body,$money,$notify_url,$cancel_url){
        $shippingPrice = 0;
        $taxPrice = 0;
        $subTotal = $money;
        $item = new Item();
        $item->setName($body)->setCurrency("USD")->setQuantity(1)->setPrice($money);
        $itemList = new ItemList();
        $itemList->addItem($item);
        $details = new Details();
        $details->setShipping($shippingPrice)->setTax($taxPrice)->setSubtotal($subTotal);
        //注意，此处的subtotal，必须是产品数*产品价格，所有值必须是正确的，否则会报错
        $total = $shippingPrice + $subTotal + $taxPrice;
        $amount = new Amount();
        $amount->setCurrency("USD")->setTotal($total)->setDetails($details);
        $transaction = new Transaction();
        $transaction->setAmount($amount)->setItemList($itemList)->setDescription($body)->setInvoiceNumber(uniqid());//setInvoiceNumber为支付唯一标识符,在使用时建议改成订单号
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');//["credit_card", "paypal"]
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($notify_url)->setCancelUrl($cancel_url);
        $payment = new Payment();
        $payment->setIntent("sale")->setPayer($payer)->setRedirectUrls($redirectUrls)->addTransaction($transaction);
        try {
            $oAuth = new  OAuthTokenCredential($this->client_id, $this->secret);
            $apiContext =  new ApiContext($oAuth);
            if($this->online == 2){
                $apiContext->setConfig(['mode' => 'live']);//设置线上环境,默认是sandbox
            }
            $payment->create($apiContext);
            if($type == 1){
                $approvalUrl = $payment->getApprovalLink();
            }else{
                $approvalUrl = $payment->id;
            }
            $result['payUrl'] = $approvalUrl;
            return ['status'=>true,'data'=>$result,'msg'=>"获取成功"];
        } catch (\Exception $e) {
            return ['status'=>false,'msg'=>$e->getMessage()];
        }
    }

    /**
     * 以下是支付成功的回调代码片段
     */
    public function payRedirect($paymentID,$payerId)
    {
        $oAuth = new OAuthTokenCredential($this->client_id, $this->secret);
        $apiContext =  new ApiContext($oAuth);
        if($this->online == 2){
            $apiContext->setConfig(['mode' => 'live']);//设置线上环境,默认是sandbox
        }
        $payment = Payment::get($paymentID, $apiContext);
        $execute = new PaymentExecution();
        $execute->setPayerId($payerId);
        try{
            $payment = $payment->execute($execute, $apiContext);//执行,从paypal获取支付结果
            $paymentState = $payment->getState();//Possible values: created, approved, failed.
            $invoiceNum = $payment->getTransactions()[0]->getInvoiceNumber();
            $payNum = $payment->getTransactions()[0]->getRelatedResources()[0]->getSale()->getId();//这是支付的流水单号，必须保存，在退款时会使用到
            $total = $payment->getTransactions()[0]->getRelatedResources()[0]->getSale()->getAmount()->getTotal();//支付总金额
            $transactionState = $payment->getTransactions()[0]->getRelatedResources()[0]->getSale()->getState();//Possible values: completed, partially_refunded, pending, refunded, denied.
            if($paymentState == 'approved' && $transactionState == 'completed'){
                //处理成功的逻辑，例如：判断支付金额与订单金额，更新订单状态等
                $responseResult['invoiceNum'] = $invoiceNum;
                $responseResult['total'] = $total;
                $responseResult['payNum'] = $payNum;
                $responseResult['paymentID'] = $paymentID;
                return ['status'=>true,'msg'=>"支付成功",'data'=>$responseResult];
            }else{
                //paypal回调错误,paypal状态不正确
                return ['status'=>false,'msg'=>"订单未支付"];
            }
        }catch(\Exception $e){
            return ['status'=>false,'msg'=>$e->getMessage()];
        }
    }


    /**
     * paypal退款
     * @param string $txn_id 异步回调sale通知中拿到的id
     * @param int $price
     */
    public function refundPaypalMoney($txn_id,$price){
        try {
            $oAuth = new OAuthTokenCredential($this->client_id, $this->secret);
            $apiContext =  new ApiContext($oAuth);
            if($this->online == 2){
                $apiContext->setConfig(['mode' => 'live']);//设置线上环境,默认是sandbox
            }
            $amt = new Amount();
            $amt->setCurrency('USD')
                ->setTotal($price);  // 退款的费用
            $refund = new Refund();
            $refund->setAmount($amt);
            $sale = new Sale();
            $sale->setId($txn_id);
            $refundedSale = $sale->refund($refund,$apiContext);
        } catch (\Exception $e) {
            return ['status' => false,'msg'=> $e->getMessage()];
        }
        $refundedSale = $refundedSale->toArray();
        // 退款完成
        if($refundedSale['state']=='completed'){
            return ['status' => true,'msg'=>"退款成功"];
        }else{
            return ['code' => false,'msg' =>'退款失败！'];
        }
    }
}