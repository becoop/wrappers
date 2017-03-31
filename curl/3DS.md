## 3DS – Platform agnostic version

The 3D Secure platform-agnostic docs are a way to integrate to our products without a wrapper.
In order to do this, you'll need as a requirement the `CURL` library installed to debug your requests and responses.

- API TEST BASE URL: https://mpi.3dsintegrator.com/index_demo.php
- API LIVE BASE URL: https://mpi.3dsintegrator.com/index.php

### Authentication

In every request, our API demands sending over two headers:
- x-mpi-api-key
- x-mpi-signature

While the `x-mpi-api-key` is your actual API key, the `x-mpi-signature` is a signed hex-digested SHA256 token, which will differ on every request based on the following variables:
- api_key - which is the `x-mpi-api-key`
- full_path – BASE URL + endpoint;
- data - JSON dumped data with keys sorted alphabetically;
- api_secret - your account's API Secret.

In order to easily debug this, we've created a script that signs your requests, so you can compare your application signature with this sample script.
- Install [jq](https://stedolan.github.io/jq/)
- Download [3ds_sign](./3ds_sign)
- Run `3ds_sign -h` for help.


### Check card enrollment

Not every card is able to run 3DS, so in this first step we check for card's enrollment status.

##### Endpoint
`/enrolled_status`

##### Request Fields
- `pan` (string). The credit card number.

##### CURL Sample:
```bash
curl -v -X POST -H 'User-Agent: Faraday v0.11.0' \
  -H 'Content-Type: application/json' \
  -H 'x-mpi-api-key: nNucSXwFw3sXYKE4NUQIZgWTPX71MLa0' \
  -H 'x-mpi-signature: 1b742fab984f86b141991917c71c99b81109078e4874ce1c9fb925a038af0386' \
  -d '{"pan":"4111111111111111"}' \
  "https://mpi.3dsintegrator.com/index.php/enrolled-status"
```

##### Response Sample
```json
{"enrollment_status":"N"}
```

### Payment Authentication Request (PAREQ)

Whenever you get a "Y" from the previous step, you're ready to start the Payment Authentication.

##### Endpoint
`/auth-request`

##### Request Fields
- `pan` (string). The credit card number.
- `card_exp_month` (string). Card expiration month in two digits.
- `card_exp_year` (string). Card expiration year in four digits.
- `amount` (float). Amount of the transaction.
- `transaction_id` (string). An unique identifier for the transaction in course.
- `message_id` (string). An unique identifier for the order.
- `return_url` (string). Callback URL for processing the transaction and sending the 3DS response through the gateway.

##### CURL Sample:
```bash
curl -v -X POST -H 'User-Agent: Faraday v0.11.0' \
  -H 'Content-Type: application/json' \
  -H 'x-mpi-api-key: nNucSXwFw3sXYKE4NUQIZgWTPX71MLa0' \
  -H 'x-mpi-signature: 16851d38cdc413e3481fedc17b03fb080eaf45a1ab20a4e6b9c7c2bb93a72799' \
  -d '{
    "amount":2,
    "card_exp_month":"12",
    "card_exp_year":"2020",
    "message_id":"0001",
    "pan":"4111111111111111",
    "return_url":"https://localhost:8000/3ds/my_callback",
    "transaction_id":"0001"
  }' \
  "https://mpi.3dsintegrator.com/index.php/auth-request"
```

##### Response Sample
```json
{
  "AcsUrl": "https://mpi.3dsintegrator.com/demoacs/",
  "PaReq": "eJx1kduOgkAMhl/FmL2mHJWQ2oRFE7nQGOV+M4FGSJaDA4i==",
  "TermUrl": "http://localhost:8000/3ds/my_callback",
  "MD": "0001"
}
```

### Redirects

After having the PAREQ token, you're ready to redirect your user to VISA or MasterCard's confirmation.
You should render a template with a form that auto submits and starts the 3DS process.

##### Strategy 1: Strict mode

The strict mode consists in actually sending the user to VISA and MasterCard's website.
\* Please note that the variables below prefixed with `$` are gathered from the response at the previous step (see above).

```html
<form name="form3ds" action="$AcsUrl" method="post"/>
  <input name="PaReq" type="hidden" value="$PaReq"/>
  <input name="MD" type="hidden" value="$MD"/>
  <input name="TermUrl" type="hidden" value="$TermUrl"/>
</form>

<script>
  window.onload = function() {
    document.form3ds.submit();
  }
</script>
```

You will receive a `POST` at the `$TermUrl` containing CAVV, ECI and XID, which are the critical data to be sent through the gateway for 3DS authentication.

##### Strategy 2: Frictionless mode

The frictionless mode consist in inserting an IFRAME to your checkout page and waiting for the 3DS response.
In this case, the `$TermUrl` should check is the parameter `PaRes` is sent through `POST`. 
- If `PaRes` param it is, you should render a JSON response and the script below will take care of final redirections;
- If `_3ds_frictionless_callback` is present, it is because the transaction has finshed.

```html
<style> #frame { display: none; } </style>
<iframe id="frame" src="about:blank"></iframe>
<form id="callback-form" method="POST" action="#{term_url}">
  <input type="hidden" name="_frictionless_3ds_callback" value="1"/>
</form>

<script>
  var formHtml = '<form name="form3ds" action="$AcsUrl" method="post"/><input name="PaReq" type="hidden" value="$PaReq"/><input name="MD" type="hidden" value="$MD"/><input name="TermUrl" type="hidden" value="$TermUrl"/></form>';

  formHtml = formHtml.replace('$AcsUrl', 'REPLACE WITH ACS_URL FROM THE PREVIOUS REQUEST');
  formHtml = formHtml.replace('$PaReq', 'REPLACE WITH PAREQ FROM THE PREVIOUS REQUEST');
  formHtml = formHtml.replace('$MD', 'REPLACE WITH MD FROM THE PREVIOUS REQUEST');
  formHtml = formHtml.replace('$TermUrl', 'REPLACE WITH TERM_URL FROM THE PREVIOUS REQUEST');

  (function(){
    var frame = document.getElementById('frame');
    var form = document.getElementById('callback-form');
    var interval = 500;
    var timeout = interval * 15;

    frame.contentDocument.write(formHtml);
    frame.contentDocument.form3ds.submit();

    var interval = setInterval(function() {
      try {
        var frameContent = frame.contentDocument;
        var frameDoc = frameContent.documentElement;

        var text = frameContent.body.innerHTML || frameDoc.textContent || frameDoc.innerText;
        var json = JSON.parse(text);

        var input;

        for(key in json) {
          input = document.createElement('input');
          input.type = 'hidden';
          input.name = key;
          input.value = json[key];

          form.appendChild(input);
        };

        clearInterval(interval);
        form.submit();
      } catch(e) {
        return false;
      };
    }, interval);

    setTimeout(function() {
      form.submit();
    }, timeout);
  })();
</script>
```

Whenever you get a `_3ds_frictionless_callback` parameter on your response at `$TermUrl`, you should check for the CAVV, ECI and XID parameters – these are required to be sent through the gateway for finishing the 3DS process.
