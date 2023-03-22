<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;
// order
/**
 *  @property int $public $order_num 주문번호
 *  @property string $bank_deposit 진행상태
 *  @property string $bank_nm 
 *  @property string $bank_num 
 *  @property string $cancel_date 
 *  @property string $card_num  
 *  @property string $copy_date  
 *  @property string $delivery  
 *  @property float $delivery_amt 
 *  @property string $delivery_date  
 *  @property string $memo 
 *  @property string $memo1 
 *  @property string $memo2  
 *  @property string $order_date 
 *  @property int $order_num 
 *  @property float $receipt_amt 
 *  @property string $receipt_date 
 *  @property string $repay_date 
 *  @property string $status 
 *  @property string $usepo 
 *  @property string $purpose 
 *  @property string $toggle true=상태변경/false=상태변경 취소 

 */

// order_customer
/**
 *  @property string $address1 주소1
 *  @property string $address2 주소2
 *  @property string $cust_bank_deposit 은행계좌
 *  @property string $cust_nm 주문자명
 *  @property string $email  이메일
 *  @property string $order_num  주문번호
 *  @property string $phone  전화번호
 *  @property string $receipt_date  신청일자
 *  @property float $zipcode 우편번호
 */

//  orderItem
/**
 * @property string $selData orderItem 목록
 */


final class archiveManagementOrderDto extends DataTransferObject
{
    public $order_num;
    public $bank_deposit;
    public $bank_nm;
    public $bank_num;
    public $cancel_date;
    public $card_num;
    public $copy_date;
    public $delivery;
    public $delivery_amt;
    public $delivery_date;
    public $memo;
    public $memo1;
    public $memo2;
    public $order_date;
    // public $order_num;
    public $receipt_amt;
    public $receipt_date_order;
    public $repay_date;
    public $status;
    public $usepo;
    public $purpose;
    public $price;
    public $toggle;



    public $address1;
    public $address2;
    public $cust_bank_deposit;
    public $cust_nm;
    public $email;
    // public $order_num;
    public $phone;
    public $receipt_date;
    public $zipcode;


    public $selData;
}
