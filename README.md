# wookyash
Woocommerce Integration Kit for the [Kyash Payment Gateway](http://www.kyash.com/).

## Installation
1. Login to your Admin Dashboard.
2. Go to `Plugins`->`Add New`->`Upload Plugin`
3. Upload the [wookyash.zip](https://github.com/Gubbi/wookyash/releases/download/1.21/wookyash-1.21.zip) file by clicking `Install now` button. Click on `Activate Plugin` link.
4. On `Plugins` page you should see *Kyash* in the plugin list.

## Configuration
1. Login to your Admin Dashboard.
2. Go to `Woocommerce`->`Settings`. On `Checkout` tab visit `Kyash - Pay at a nearby shop` to fill in the credentials (Available in your Kyash Account Settings page).
3. There are two types of credentials you can enter: 
  - To test the system, use the *Developer* credentials.
  - To make the system live and accept your customer payments use the *Production* credentials.
4. Copy the *Callback URL* (e.g. `http://www.yourstore.com/?action=kyash-handler`) to your Kyash Account Settings and click `Set` to update the callback URL.

## Testing the Integration.
1. Place an order from your Woocommerce store.
2. Pick *Kyash - Pay at a nearby shop* as the payment option.
3. Note down the *KyashCode* generated for this order.
4. In a live system, the customer will take this KyashCode to a nearby shop and make the payment using cash.
5. But since we are testing, Login to your Kyash Account.
6. Enter the KyashCode in the search box.
7. You should see a `Mark as Paid` button there.
8. Clicking this should change the order status from *On Hold* to *Processing* in your Woocommerce order details page.

## Troubleshooting
### Paid and Expired KyashCodes are not being marked as such in Woocommerce.
Once you have successfully installed Kyash plugin, if your orders are not being marked as paid after payment is done, then follow the below steps.

* Configure the Kyash plugin using your Kyash *Development API Credentials*.
* Create a test order with Kyash as the payment option.
* Note down the KyashCode returned.
* Login to your Kyash account and search for the KyashCode.
* Mark it as Paid.
* Check if the order status changes from "On Hold" to "Processing" in Woocommerce.
* If the status has not changed, then make the following entry in your .htaccess file just after the `RewriteEngine On` entry.
```
RewriteCond %{HTTP:Authorization} .+
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```
* Create another test order and mark it as paid to see if the issue is now fixed.

## Support
Contact developers@kyash.com for any issues you might be facing with this Kyash extension or call +91 8050114225.
