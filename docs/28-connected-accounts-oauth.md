# Connected Accounts & Social Login (OAuth)

Connected accounts let visitors log in and register on a XenForo board using
external providers (Google, Facebook, Apple, etc.). Each provider requires you
to register a developer **application** and enter its credentials into the
control panel.

> **Sources (official XenForo manual):**
> - https://docs.xenforo.com/manual/configuration/connected-accounts/
> - https://docs.xenforo.com/manual/configuration/connected-accounts/apple
> - https://docs.xenforo.com/manual/configuration/connected-accounts/facebook
> - https://docs.xenforo.com/manual/configuration/connected-accounts/github
> - https://docs.xenforo.com/manual/configuration/connected-accounts/google
> - https://docs.xenforo.com/manual/configuration/connected-accounts/linkedin
> - https://docs.xenforo.com/manual/configuration/connected-accounts/microsoft
> - https://docs.xenforo.com/manual/configuration/connected-accounts/twitter
> - https://docs.xenforo.com/manual/configuration/connected-accounts/yahoo

---

## Overview

The ability for visitors to log in and register via various connected account providers benefits site owners because new accounts can be created easily. It reduces the friction of creating an account or remembering login details, which can lead to increased engagement.

To use this functionality you must register your **application** with the provider and fill in some basic developer details. This provides the necessary integration between the forum software and the connected account provider.

The two pieces of information almost every provider gives you are a **key / client ID** and a **secret / client secret**. To enter them, log in to the Admin Control Panel, go to **Setup > Connected accounts**, click the provider in the list, fill in its fields and save. Each per-provider "Configuring" step below is shorthand for this path.

### The callback / redirect URI

Every provider needs a redirect (callback) URL pointed back at your board. For all documented providers this is your board URL followed by `connected_account.php` — for example, `https://xenforo.com/community/connected_account.php`.

> **The beginning of this URL must match your *Board URL* setting in XenForo exactly.** Several providers (Facebook, Microsoft) additionally require the Board URL to use **HTTPS**, and Apple requires a valid SSL certificate. If users can reach your site on more than one hostname (for example with and without `www.`, or with both `http` and `https`), some providers — notably Google and Microsoft — require you to register **every** variation.

### Testing connected accounts

After you have set up a connected account using the instructions for the particular service, confirm everything is configured and working with the *test tool*:

1. Select **Setup** from the navigation panel, and click on **Connected
   accounts**.
2. Click on **Test provider** next to the entry you'd like to test in the list
   of providers.
3. Click on the **Test** button.

If the test is successful, the resulting screen shows the name, email address and profile picture (where applicable) of the account associated with the provider. If unsuccessful, confirm that your **Board URL** is correct and that the correct details have been entered for the key and secret.

After a successful test, visitors can log in and register using their respective accounts. The first time they do so, they are required to allow the application access to their account.

### Changing the metadata share logo

When a user shares a page to other sites, those sites either display a nominated image or attempt to pick one from the page. To nominate an image for all pages, define a **Metadata logo URL** in style properties: in the control panel go to **Appearance > Style properties > Basic options** and change the **Metadata logo URL** to point to a logo you've uploaded. This should generally be a square image and as large as possible.

> **Note:** Due to caching, it can take several weeks for the image to update on
> the service provider's servers.

### For add-on developers

Connected-account providers are an extension point: the provider list shown under **Setup > Connected accounts** is what each provider's credentials are configured against, and every provider integrates through the shared `connected_account.php` callback endpoint described above. The XenForo manual pages cited here document the per-provider setup (creating the app and entering the key/secret) but do not document a PHP class API for registering a new provider, so that is intentionally omitted.

---

## Providers

The table below summarizes what each provider calls its credentials and any
notable pre-requisites. Details follow in the per-provider sections.

| Provider | Credential 1 | Credential 2 | Notable pre-requisites |
|---|---|---|---|
| Apple | Services ID | Key (file) + Key ID + Team ID | Paid Apple Developer Program; valid SSL |
| Facebook | App ID | App secret | Facebook account; HTTPS Board URL |
| GitHub | Client ID | Client Secret | GitHub account |
| Google | Client ID | Client secret | Google account |
| LinkedIn | Client ID | Client Secret | LinkedIn account + business page |
| Microsoft | Application (client) ID | Secret **Value** | Microsoft account; HTTPS required |
| Twitter / X | Consumer key (API key) | Consumer Secret (API secret key) | Twitter developer account |
| Yahoo | Client ID | Client Secret | Yahoo account |

---

### Apple

#### Pre-requisites

Before configuring "Sign in with Apple" you must first join the
[Apple Developer Program](https://developer.apple.com/) and pay for a full
developer account. You must also ensure your site is available over an SSL
(https) connection with a valid certificate.

#### Creating a new application

1. Log in to your [Apple Developer Account](https://developer.apple.com/account/).
2. Under "Certificates, IDs & Profiles" click **Identifiers**.
3. Click the "plus" icon followed by "App IDs" and click "Continue".
4. Select "App" as the type and click "Continue".
5. Note down the value of "App ID Prefix" which is your **Team ID**. This will be needed later.
6. Enter a "Description" and with "Explicit" selected, type a "Bundle ID". Reverse-domain name style is suggested, but it doesn't have to match your domain name exactly (example: `com.xenforo.community`).
7. Under "Capabilities" find "Sign In with Apple" and click the checkbox.
8. Click "Save" and when the page reloads click "Register".
9. Back on the "Identifiers" list, click the "plus" icon again, but this time select "Services IDs" before clicking "Continue".
10. Provide a "Description" and an "Identifier". The board/site title is recommended for the description; reverse-domain name style is suggested for the identifier (example: `com.xenforo.community.service`).
11. Click "Continue" followed by "Register".
12. Back on the "Identifiers" list, ensure it is filtered by "Services IDs" in the top right.
13. Click the "Services ID" you just created.
14. Find "Sign In with Apple" in the list and click "Configure".
15. In the overlay, provide the actual domain name for your website in the "Domain and Subdomains" field (example: `xenforo.com`).
16. Under "Return URLs" type your board URL and its `connected_account.php` URL (example: `https://xenforo.com/community/connected_account.php`).
17. Click "Next" followed by "Done" to close the overlay. Now click "Continue" followed by "Save".
18. Back on the "Identifiers" list, in the left-hand navigation click "Keys".
19. On the "Register a New Key" page, enter a "Key Name".
20. Find "Sign in with Apple" in the list below, click the checkbox and click "Continue".
21. Under "Primary App ID" select the app ID you created in steps 3-8, followed by "Save".
22. Click "Continue" followed by "Register".
23. Click "Download" to download the key and note the **Key ID**.

#### Configuring Sign In with Apple connected account

Under **Setup > Connected accounts**, click **Apple** and enter your **Team ID**
(noted in step 5 above), your **Services ID** (created in step 10 above, e.g.
`com.xenforo.community.service`) and your **Key ID** (noted in step 23 above).
Use the "Choose file" button to upload the key you downloaded in step 23, then
save and [test the connected account](#testing-connected-accounts).

#### Configuring Email for Apple Private Relay support

Apple iCloud customers can hide their email addresses when using Sign in with Apple, which sets up a randomised, anonymous email forwarder to their real address. To enable seamless transmission of email to these users, tell Apple about your mail domain names and email addresses:

1. Log in to your [Apple Developer Account](https://developer.apple.com/account/).
2. Under "Certificates, IDs & Profiles" click **Services**.
3. Under "Sign in with Apple for Email Communication" click "Configure".
4. Click the "plus" icon next to "Email Sources" to open the "Register your email sources" overlay.
5. Under "Domains and Subdomains" enter your email domain(s) (example: `xenforo.com`).
6. Under "Email Addresses" enter your default email address as configured in your Admin control panel (example: `contact@xenforo.com`).
7. Click "Next" followed by "Register", followed by "Done".

---

### Facebook

A Facebook account is required to create an application. Note that you must be
logged in as a person, not a page.

#### Creating a Facebook application

1. Browse to https://developers.facebook.com/ and be sure you're logged into your Facebook account.
2. Click the **My Apps** button at the top and then click on the **Add a New App** link.
3. Provide a name and email and then click **Create App ID**.
4. Next you should see a page called *Add Product*; if you do not, click the *Plus Icon* on the left next to the *Products* heading. Under **Facebook Login**, click **Setup**.
5. Choose the platform. Click **Web** and enter the URL to your site. Click **Next** through all of the steps.
6. In the sidebar on the left, click **Settings** under *Facebook Login*.
7. In **Valid OAuth redirect URIs**, enter `<XF board URL>/connected_account.php` (for example, `https://xenforo.com/community/connected_account.php`). The beginning of this URL must match your *Board URL* setting exactly, and the Board URL *requires HTTPS*. Click **Save Changes** at the bottom.
8. In the sidebar, click **Settings** followed by **Basic**. For the **Privacy Policy URL** and the **Terms of Service URL**, enter the links to those pages on your site.
9. In the sidebar, click **App Review**. If the app is listed as in development, click the toggle next to it to make it live/public.
10. Go back to **Settings > Basic** and make a note of the **App ID** and **App Secret**.

#### Configuring Facebook connected account

Under **Setup > Connected accounts**, click **Facebook**, enter the **App ID**
and **App secret** into the respective fields, save and
[test the connected account](#testing-connected-accounts).

---

### GitHub

#### Creating the GitHub application

1. Browse to https://github.com/settings/developers/ and log in with your GitHub account.
2. Under OAuth Apps click **Register a new application**.
3. Fill out the form as necessary:
   - The **Application Name** is displayed to users when they attempt to register via GitHub.
   - The **Homepage URL** should be set to the **Board URL** value set in XenForo.
   - The **Description** is optional.
   - The **Authorization callback URL** should be set to `<XF board URL>/connected_account.php` (for example, `https://xenforo.com/community/connected_account.php`). The beginning of this URL must match your *Board URL* setting exactly.
4. Once you click **Register Application**, note down the **Client ID** and **Client Secret**.

#### Configuring GitHub connected account

Under **Setup > Connected accounts**, click **GitHub**, enter the **Client ID**
and **Client Secret** into the respective fields, save and
[test the connected account](#testing-connected-accounts).

---

### Google

Note that the email address associated with your Google account may be displayed when users register using their Google account.

#### Creating the Google project

1. Browse to https://cloud.google.com/console/project and log in with your Google account.
2. Click the **Create Project** button and enter a name and ID. These will only be used internally.
3. Once the project is created, click the hamburger menu icon at the top left, then select **APIs & Services**, then **Credentials** in the sidebar, and finally **OAuth Consent Screen**; complete the details as necessary and save.
4. Click **CREATE CREDENTIALS**, select **OAuth Client ID**, then **WEB APPLICATION** and complete the details:
   - In the **AUTHORIZED JAVASCRIPT ORIGINS** fields, enter your domain URL without the trailing slash (for example, `https://xenforo.com`). If users access your site both with and without `www`, enter both URLs; if they can access it both with and without HTTPS, enter an `http` and an `https` value. Place each URL on its own line and ensure **all** variations are entered.
   - In the **AUTHORIZED REDIRECT URIS** field, enter `<XF board URL>/connected_account.php` (for example, `https://xenforo.com/community/connected_account.php`). The beginning of this URL must match your *Board URL* setting exactly.
   - Double-check all URLs are correct, then click the **Create Client ID** button. When the **Create Client ID** overlay appears, click **Cancel**.
5. On the Credentials page, make a note of the **CLIENT ID** and **CLIENT SECRET**.

To change the values displayed when a user attempts to register via Google, customize this in your Google project via **APIs & auth > Consent screen**.

#### Configuring Google connected account

Under **Setup > Connected accounts**, click **Google**, enter the **Client ID**
and **Client secret** into the respective fields, save and
[test the connected account](#testing-connected-accounts).

---

### LinkedIn

#### Creating the LinkedIn application

1. Before you can create a new app, browse to https://www.linkedin.com/company/setup/new/ and set up a new business page. The time it takes for this to show up on the app creation page can vary from hours to weeks.
2. Browse to https://www.linkedin.com/secure/developer?newapp= and log in with your LinkedIn account.
3. Click **Create app**.
4. Fill out the form as necessary:
   - The **Company Name** is displayed to users when they attempt to register via LinkedIn; use the one you created previously.
   - The **Application Name** is displayed to users when they attempt to register via LinkedIn.
   - The **Description** is displayed to users when they attempt to register via LinkedIn.
   - Fill in your **Email** and **Privacy Policy** as required.
   - Upload an **App Logo** as required.
   - The Application Products will use **Share on LinkedIn and Sign in with LinkedIn**.
   - Click **Create App**.
   - Under the **Authentication** tab note down the **Client ID** and **Client Secret**.
   - Under **OAuth2 Authorized Redirect URLs** set this to `<XF board URL>/connected_account.php` (for example, `https://xenforo.com/community/connected_account.php`). The beginning of this URL must match your *Board URL* setting exactly.
   - Click **Update**.

#### Configuring LinkedIn connected account

Under **Setup > Connected accounts**, click **LinkedIn**, enter the **Client
ID** and **Client Secret** into the respective fields, save and
[test the connected account](#testing-connected-accounts).

---

### Microsoft

#### Creating the Microsoft application

1. Browse to https://apps.dev.microsoft.com/ and log in with your Microsoft account.
2. If prompted, click **Add an App in the Azure Portal**.
3. Click the **New Registration** button.
4. Fill out the form as necessary:
   - The **Application Name** is displayed to users when they attempt to register via Microsoft.
   - The **Supported Account Type** is **Personal**.
   - The **Redirect URI** type is **Web** and should be set to `<XF board URL>/connected_account.php` (for example, `https://xenforo.com/community/connected_account.php`). The beginning of this URL must match your *Board URL* setting exactly. If your site is reachable from both `www.` and a non-`www.` domain name, add both as possible Redirect URLs. *HTTPS* is required.
5. Click **Register**.
6. Note down the **Application (client) ID**.
7. Click **Certificates and Secrets** in the left-hand menu.
8. Click **New Client Secret**.
9. Give the secret a name and set the expiry date to **Never**.
10. Click **Add**.
11. Note down the Secret **Value**.

#### Configuring Microsoft connected account

Under **Setup > Connected accounts**, click **Microsoft**, enter the
**Application (client) ID** and Secret **Value** into the respective fields, save
and [test the connected account](#testing-connected-accounts).

---

### Twitter / X

#### Creating the Twitter application

1. Browse to https://developer.twitter.com/ and log in with your Twitter account.
2. After logging in, hover over your username in the top right corner and from the menu select **Apps**.
3. Click **Create an app**.
4. Fill out the form as necessary:
   - The **name** and **description** are displayed to users when they attempt to register via Twitter.
   - The **website URL** should be set to the **Board URL** value set in XenForo. It is very important that the correct domain is entered here; registration only works if the request comes from the domain entered here.
   - Click **Enable Sign in with Twitter**.
   - The **callback URL** should be set to `<XF board URL>/connected_account.php` (for example, `https://xenforo.com/community/connected_account.php`). The beginning of this URL must match your *Board URL* setting exactly.
   - You need to explain how the app will be used. Words to the effect of "This app will be used to provide log in/sign up with Twitter functionality so that users can log in and register to the forum with their Twitter accounts."
5. After creating the application, you are redirected to a page displaying information about the application. Click the **Keys and tokens** tab.
6. On the same page, make a note of your **API key** and **API secret key** below the **Consumer API keys** heading.

#### Configuring Twitter connected account

Under **Setup > Connected accounts**, click **Twitter**, enter the **Consumer
key** and **Consumer Secret** into the respective fields, save and
[test the connected account](#testing-connected-accounts).

---

### Yahoo

#### Creating the Yahoo application

1. Browse to https://developer.yahoo.com/apps/ and log in with your Yahoo account.
2. Click the **Create an App** button.
3. Fill out the form as necessary:
   - The **Application Name** is displayed to users when they attempt to register via Yahoo.
   - The **Application Type** is a **Web Application**.
   - The **Description** is optional.
   - The **Homepage URL** should be set to the **Board URL** value set in XenForo.
   - The **Redirect URI** should be set to `<XF board URL>/connected_account.php` (for example, `https://xenforo.com/community/connected_account.php`). The beginning of this URL must match your *Board URL* setting exactly.
   - In **API permissions** tick **OpenID Connect Permissions**, **Email** and **Profile**.
   - Once you click **Create App**, note down the **Client ID** and **Client Secret**.

#### Configuring Yahoo connected account

Under **Setup > Connected accounts**, click **Yahoo**, enter the **Client ID**
and **Client Secret** into the respective fields, save and
[test the connected account](#testing-connected-accounts).

---

## Checklist

- [ ] Provider application created and credentials (key/secret) copied.
- [ ] Redirect/callback URI set to `<XF board URL>/connected_account.php`.
- [ ] Callback URL prefix matches the **Board URL** setting exactly.
- [ ] HTTPS used where required (Apple, Facebook, Microsoft).
- [ ] All hostname variations registered where required (Google, Microsoft).
- [ ] Credentials entered under **Setup > Connected accounts** and saved.
- [ ] Provider verified with **Test provider** before going live.

**See also:** `docs/08-permissions-options-phrases.md` (options & phrases),
`docs/10-rest-api.md` and `docs/25-rest-api-endpoints.md` (API authentication).
