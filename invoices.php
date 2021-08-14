<?php
ini_set('display_errors', 'On');
require __DIR__ . '/vendor/autoload.php';
require_once('storage.php');


$message="";
$storage = new StorageClass();


if (isset($_POST["submit"])) {

    if (!validations()) {
        $_SESSION["message"] = "All fields are required";
        header('Location: ' . './invoices.php');
        exit();
    }

    $xeroTenantId = (string)$storage->getSession()['tenant_id'];

    if ($storage->getHasExpired()) {
        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId' => 'A373D6E11822480488D5E18E1FCACE0F',
            'clientSecret' => '8s8Jxth_x5znVadQbN3gfGdAlHsiHyIyIOLEAwnkPzzG5',
            'redirectUri' => 'http://localhost/xero-php-oauth2-starter/callback.php',
            'urlAuthorize' => 'https://login.xero.com/identity/connect/authorize',
            'urlAccessToken' => 'https://identity.xero.com/connect/token',
            'urlResourceOwnerDetails' => 'https://api.xero.com/api.xro/2.0/Organisation'
        ]);

        $newAccessToken = $provider->getAccessToken('refresh_token', [
            'refresh_token' => $storage->getRefreshToken()
        ]);

        // Save my token, expiration and refresh token
        $storage->setToken(
            $newAccessToken->getToken(),
            $newAccessToken->getExpires(),
            $xeroTenantId,
            $newAccessToken->getRefreshToken(),
            $newAccessToken->getValues()["id_token"]);
    }

    $config = XeroAPI\XeroPHP\Configuration::getDefaultConfiguration()->setAccessToken((string)$storage->getSession()['token']);
    $apiInstance = new XeroAPI\XeroPHP\Api\AccountingApi(
        new GuzzleHttp\Client(),
        $config
    );


    $summarizeErrors = true;
    $unitdp = 4;

    $dateValue = new DateTime();
    $dueDateValue = new DateTime("2021-12-01");

    $contact = new XeroAPI\XeroPHP\Models\Accounting\Contact;
    $contact->setContactID($_POST["contact_id"]);

    $lineItemTracking = new XeroAPI\XeroPHP\Models\Accounting\LineItemTracking;
    $lineItemTracking->setTrackingCategoryID($_POST["tracking_category_id"]);
    $lineItemTracking->setTrackingOptionID($_POST["tracking_option_id"]);
    $lineItemTrackings = [];
    array_push($lineItemTrackings, $lineItemTracking);

    $lineItem = new XeroAPI\XeroPHP\Models\Accounting\LineItem;
    $lineItem->setDescription($_POST["item_description"]);
    $lineItem->setQuantity((int)$_POST["item_quantity"]);
    $lineItem->setUnitAmount($_POST["item_unit_amount"]);
    $lineItem->setAccountCode($_POST["account_code"]);
    $lineItem->setTracking($lineItemTrackings);
    $lineItems = [];
    array_push($lineItems, $lineItem);

    $invoice = new XeroAPI\XeroPHP\Models\Accounting\Invoice;
    $invoice->setType(XeroAPI\XeroPHP\Models\Accounting\Invoice::TYPE_ACCREC);
    $invoice->setContact($contact);
    $invoice->setDate($dateValue);
    $invoice->setDueDate($dueDateValue);
    $invoice->setLineItems($lineItems);
    $invoice->setReference($_POST["reference"]);
    $invoice->setStatus(XeroAPI\XeroPHP\Models\Accounting\Invoice::STATUS_DRAFT);

    $invoices = new XeroAPI\XeroPHP\Models\Accounting\Invoices;
    $arr_invoices = [];
    array_push($arr_invoices, $invoice);
    $invoices->setInvoices($arr_invoices);

//    try {
        $result = $apiInstance->createInvoices($xeroTenantId, $invoices, $summarizeErrors, $unitdp);
        dump($result);
        die();
        $_SESSION["message"]="Invoice created successfully";
//    } catch (Exception $e) {
//        echo 'Exception when calling AccountingApi->createInvoices: ', $e->getMessage(), PHP_EOL;
//    }

}

function validations()
{

    $fields = [
        "contact_id", "tracking_category_id", "tracking_option_id",
        "item_description", "item_quantity", "item_unit_amount", "account_code", "reference",
    ];
    $response = true;
    foreach ($fields as $field) {

        if (!array_key_exists($field, $_POST)) {
            return false;
        }
    }
    return $response;
}

?>


<html>
<head>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
          integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
</head>

<body>

    <div class="container">
        <form class="mt-4" method="POST" action="invoices.php">

            <div class="row">
                <div class="form-outline mb-4 col-md-6">
                    <label class="form-label" for="form1Example1">Contact ID</label>
                    <input type="text" id="form1Example1" class="form-control" name="contact_id" required/>
                </div>

                <div class="form-outline mb-4 col-md-6">
                    <label class="form-label" for="form1Example1">Tracking Category ID</label>
                    <input type="text" id="form1Example1" class="form-control" name="tracking_category_id" required/>
                </div>
            </div>

            <div class="row">
                <div class="form-outline mb-4 col-md-6">
                    <label class="form-label" for="form1Example1">Tracking Option ID</label>
                    <input type="text" id="form1Example1" class="form-control" name="tracking_option_id" required/>
                </div>

                <div class="form-outline mb-4 col-md-6">
                    <label class="form-label" for="form1Example1">Item Description</label>
                    <input type="text" id="form1Example1" class="form-control" name="item_description" required/>
                </div>
            </div>

            <div class="row">
                <div class="form-outline mb-4 col-md-6">
                    <label class="form-label" for="form1Example1">Item Quantity</label>
                    <input type="number" id="form1Example1" class="form-control" name="item_quantity" required/>
                </div>

                <div class="form-outline mb-4 col-md-6">
                    <label class="form-label" for="form1Example1">Item Unit Amount</label>
                    <input type="number" id="form1Example1" class="form-control" name="item_unit_amount" required/>
                </div>
            </div>

            <div class="row">
                <div class="form-outline mb-4 col-md-6">
                    <label class="form-label" for="form1Example1">Account Code</label>
                    <input type="text" id="form1Example1" class="form-control" name="account_code" required/>
                </div>

                <div class="form-outline mb-4 col-md-6">
                    <label class="form-label" for="form1Example1">Reference</label>
                    <input type="text" id="form1Example1" class="form-control" name="reference" required/>
                </div>
            </div>
            <!-- Submit button -->
            <button type="submit" name="submit" class="btn btn-primary btn-block">Create Invoice</button>
        </form>

    </div>
<div>
    <?php
    if(isset($_SESSION["message"]))
        echo($_SESSION["message"]);
    ?>
</div>
</body>

<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
</html>
