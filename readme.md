# WHMCS Discord Connection
This will let paying customers get a Discord role by going to /discord.php.
I will not be providing support for this repository.

If you like it then give it a star! 🌟

## Installation
1. Add the `discord.php` file to the root of your WHMCS installation.

2. Create a Discord App with a bot token, and set the OAuth redirect URI to `https://billing.example.com/discord.php` with replacing the domain by the specific of your WHMCS installation.

3. Change the variables in `discord.php` with the generated keys before.

4. [Create a custom field to customers called `discord`. This will contain the customer's Discord ID.](https://docs.whmcs.com/Custom_Client_Fields)

5. Add the `discord.tpl` to your theme's directory.

6. Enjoy ! (you can add a button at the home of your client area, or just communicate the specific URL to your customers)
