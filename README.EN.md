[**Wersja polska**][ext0]

# OpenCart Payment Extension for Paynow

Paynow plugin adds quick bank transfers and BLIK payment to OpenCart shop.

This plugin supports OpenCart 3.0 and higher.

## Installation

1. Download the paynow.ocmod.zip file from [Github repository][ext1] to the local directory as zip file
2. Go to the OpenCart administration page
3. Go to `Extensions > Installer`

![Installation step 3][ext3]

4. In the section `Upload your extension` use the ption `Upload` and point to the archive containing the plugin (downloaded in the 1st step)

## Configuration

1. Go to `Extensions > Extensions`
2. From the filter list chose `Payments`

![Configuration step 2][ext5]

3. Find `Paynow` and click `Edit`

![Configuration step 3][ext6]

4. Production credential keys can be found in `Settings > Shops and poses > Authentication data` in the Paynow merchant panel.

   Sandbox credential keys can be found in `Settings > Shops and poses > Authentication data` in the [sandbox panel][ext11].

![Configuration step 4][ext8]

5. Depending on the environment you want to connect type:

- for the production environment
  - `API Key (Production)`
  - `Signature Key (Production)`
- for the sandbox environment
  - `API Key (Sandbox)`
  - `Signature Key (Sandbox)`

## FAQ

**How to configure the notification address?**

The notification address will be set automatically for each order. Otherwise in the Paynow merchant panel go to the tab `Settings > Shops and poses`, in the field `Notification address` set the address: `https://your-domain.pl/index.php?route=extension/payment/paynow/notifications`.

**How to configure the return address?**

The return address will be set automatically for each order. Otherwise in the Paynow merchant panel go to the tab `Settings > Shops and poses > Point of sales`, in the field `Return address` set the address: `https://your-domain.pl/index.php?route=checkout/success`.

![FAQ][ext12]

## Sandbox

To be able to test our Paynow Sandbox environment register [here][ext2]

## Support

If you have any questions or issues, please contact our support on kontakt@paynow.pl.

## More info

If you wish to learn more about Paynow visit our website: https://www.paynow.pl/

## License

MIT license. For more information, see the LICENSE file.

[ext0]: README.EN.md
[ext1]: https://github.com/pay-now/paynow-opencart/releases/latest
[ext2]: https://panel.sandbox.paynow.pl/auth/register
[ext3]: instruction/step1.png
[ext4]: instruction/step2.png
[ext5]: instruction/step3.png
[ext6]: instruction/step4.png
[ext7]: instruction/step5.png
[ext8]: instruction/step6.png
[ext9]: instruction/step7.png
[ext10]: instruction/step8.png
[ext11]: https://panel.sandbox.paynow.pl/merchant/settings/shops-and-pos
[ext12]: instruction/faq.png
