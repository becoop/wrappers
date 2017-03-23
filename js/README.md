In order to use the PayCertify Checkout JS Plugin, there are no dependencies.

# Setup

Download paycertify.js from the `dist` directory by [clicking here](https://github.com/PayCertify/wrappers/blob/master/js/dist/paycertify.js);


Link it on your application: 
```html
<script type='text/javascript' src='path/to/paycertify.js'></script>
```


Set the `data-paycertify` data attributes to your form elements as below. All the fields are mandatory.
```html
<form target="/somewhere-in-my-app">
  <label for="name">Name</label><br/>
  <input name="name" data-paycertify="name"/><br/><br/>

  <label for="email">Email</label><br/>
  <input name="email" data-paycertify="email"/><br/><br/>

  <label for="phone">Phone</label><br/>
  <input name="phone" data-paycertify="phone"/><br/><br/>

  <label for="address">Address</label><br/>
  <input name="address" data-paycertify="address"/><br/><br/>

  <label for="city">City</label><br/>
  <input name="city" data-paycertify="city"/><br/><br/>

  <label for="state">State</label><br/>
  <input data-paycertify="state"/><br/><br/>

  <label for="country">Country</label><br/>
  <input data-paycertify="country"/><br/><br/>

  <label for="zip">ZIP</label><br/>
  <input data-paycertify="zip"/><br/><br/>

  <input type="hidden" name="amount" data-paycertify="amount" value="1.00"/>

  <input type="submit"/>
</form>
```

After linking it to your form, just instantiate a new PayCertify.Checkout!
```js
new PayCertify.Checkout({
  // The PayCertify Fraud Portal *PUBLIC* API Key.
  // Log in to paycertify.com to get this info or
  // ask for it for PayCertify's support team.
  apiKey: 'Your Public API Key',
  
  // Set of rules to prevent fraudulent transactions from happening.
  rejectWhen: {
    // mode can be 'and' / 'or'. when and, all options should be matched. 
    // when or, if one option fails, the transaction will be halted.
    // Default: 'and'
    mode: 'and', 

    // Options for recommendation are decline and review.
    // Default: ['decline']
    recommendation: ['decline', 'review'],

    // Maximum amount of rules that can be triggered to pass through.
    // Default: 1
    maxRulesTriggered: 1,

    // Maximum score tolerated. Minimum is 1 and maximum is 99.
    // Default: 50
    maxScore: 50,
  }
});
```


To append error messages to your form, use the following event listener:
```js
// Add a listener to manage the error messages and append it as you'd
// like to your design. e.detail contains an object with the errors.
//
window.addEventListener('paycertifyCheckoutFailure', function (e) {
  console.log(e.detail);
}, false);
```

If you run into any issues, please contact us at [engineering@paycertify.com](mailto:engineering@paycertify.com)
