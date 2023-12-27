{curl_request url='https://api.example.com/data'}
{if $whitelabeled == true}
<div class="payment-method">
    <label class="payment-method-label">Select a payment method:</label>
    <div class="radio-buttons">
        {if $knet == true}
        <label class="radio-container">Knet
            <input type="radio" name="payment_info[upay_payment_method]" value="knet" required>
            <span class="checkmark"></span>
        </label>
        {/if}
        {if $credit_card== true}
        <label class="radio-container">Credit Card
            <input type="radio" name="payment_info[upay_payment_method]" value="cc">
            <span class="checkmark"></span>
        </label>
        {/if}
        {if $samsung_pay == true}
        <label class="radio-container">Samsung Pay
            <input type="radio" name="payment_info[upay_payment_method]" value="samsung-pay">
            <span class="checkmark"></span>
        </label>
        {/if}
        {if $apple_pay== true}
        <label class="radio-container">Apple Pay
            <input type="radio" name="payment_info[upay_payment_method]" value="apple-pay">
            <span class="checkmark"></span>
        </label>
        {/if}
        {if $google_pay== true}
        <label class="radio-container">Google Pay
            <input type="radio" name="payment_info[upay_payment_method]" value="google-pay">
            <span class="checkmark"></span>
        </label>
        {/if}
        {if $amex== true}
        <label class="radio-container">Amex
            <input type="radio" name="payment_info[upay_payment_method]" value="amex">
            <span class="checkmark"></span>
        </label>
        {/if}
        <!-- Add more payment methods as needed -->
    </div>
</div>
{else}
<div class="payment-method">
    <label class="payment-method-label">Select a payment method:</label>
    <div class="radio-buttons">
        <label class="radio-container">Upayments
            <input type="radio" name="payment_info[upay_payment_method]" value="upayments" required>
            <span class="checkmark"></span>
        </label>
        <!-- Add more payment methods as needed -->
    </div>
</div>
{/if}
<style>
    .radio-container {
    display: block;
    position: relative;
    padding-left: 35px;
    margin-bottom: 15px;
    cursor: pointer;
    font-size: 14px;
}

.radio-container input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

.checkmark {
    position: absolute;
    top: 0;
    left: 0;
    height: 25px;
    width: 25px;
    background-color: #eee;
    border-radius: 50%;
}

.radio-container input:checked ~ .checkmark {
    background-color: #2196F3; /* Change the background color when the radio button is checked */
}

.checkmark:after {
    content: "";
    position: absolute;
    display: none;
}

.radio-container input:checked ~ .checkmark:after {
    display: block;
}

.radio-container .checkmark:after {
    top: 9px;
    left: 9px;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: white;
}

</style>