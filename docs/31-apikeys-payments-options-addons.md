# API Keys, Payments, Options & Add-on Management (ACP)

Admin-side configuration in the XenForo 2.3 Admin Control Panel (ACP) for the
REST API keys, payment profiles, the options system, installing/managing
add-ons, and closing the board. This page documents the **admin** workflows as
they appear in the official manual — it does not cover the developer-side APIs.

> **Sources** (official XenForo manual — verbatim):
> - https://docs.xenforo.com/manual/configuration/api-keys
> - https://docs.xenforo.com/manual/configuration/payments
> - https://docs.xenforo.com/manual/configuration/options
> - https://docs.xenforo.com/manual/configuration/add-ons
> - https://docs.xenforo.com/manual/configuration/closing

---

## REST API keys

XenForo from version **2.1 and onwards** has a REST API available. This allows
external systems and applications that are **not** running directly *through*
XenForo to access data from your forum.

### Key management

To work with the API, external API clients must possess a suitable **API key**,
which grants permission to access the necessary data.

API keys are created and managed in the ACP at:

**Setup > Service providers > API keys**

> **Best practice (from the manual):** create a separate key for every separate
> client of the API. That way each client receives *precisely* the permissions
> it requires and nothing more, and you can revoke access for one client without
> affecting the others.

### Endpoints

The API features a number of endpoints and actions that can be taken.
Additional endpoints and data may be added in the future.

| Resource | Where |
|---|---|
| Full description of API key types and how to work with them | XenForo developer documentation — https://xenforo.com/docs/dev/rest-api/ |
| The API endpoint documentation | https://xenforo.com/community/pages/api-endpoints/ |

> **For add-on developers:** the manual page only covers creating and managing
> keys from the ACP. For consuming the REST API and the full description of key
> types, see `docs/10-rest-api.md`; for the endpoint catalogue, see
> `docs/25-rest-api-endpoints.md`.

---

## Payment profiles

XenForo provides the ability to handle payments from your users using a variety
of payment processors. Several of these allow credit and debit card payments to
be accepted.

Payments can currently be handled through:

| Processor |
|---|
| Stripe |
| PayPal |
| BrainTree |
| 2Checkout |

### Setting up a payment profile

To set up a payment processor, configure a **Payment profile** via:

**Options > Service providers > Payment profiles > Add payment profile**

1. Select the processor you want to use from the list.
2. Enter all the required configuration data according to the field
   descriptions on the form.

Once a payment profile is successfully defined, you will be able to add it to
the list of payment options your users may choose when purchasing a **User
upgrade**, to buy or subscribe to access to restricted features or content you
have set up.

---

## The options system

XenForo includes a wide range of options to allow you to configure your forum
to fit your needs. Most of these options are straightforward, so they are not
detailed individually in the manual; specific information is provided only for
the more complex options.

The main XenForo options system is found in the **Setup** section of the ACP,
under **Options**.

### Advanced options

While the majority of XenForo's options are visible and editable at all times,
some options are kept behind the curtain of **advanced mode**. The stated
reasons in the manual are:

- to prevent accidental editing of important settings, and
- to keep page clutter to a minimum and show only options that are commonly
  edited.

Some options may be available **only** when advanced mode is active.

To access advanced options, either:

- click the **Show advanced options** option in the footer of the gears menu in
  the header of the Admin control panel, or
- click the **Show advanced options** button on the main option groups listing
  page.

> **For add-on developers:** the ACP-side options system shown here is where the
> options your add-on defines appear to administrators. Defining option groups
> and options for your add-on is covered in
> `docs/08-permissions-options-phrases.md`.

---

## Add-on management

XenForo includes an extensive framework for add-ons to extend and change
XenForo's functionality, generally without you having to make any changes by
hand. Many add-ons can be downloaded from the XenForo community resources
(https://xenforo.com/community/resources/).

### Support considerations

Although many add-ons focus on adding new areas to XenForo, they always interact
with the core of XenForo and thus can introduce unexpected behavior and bugs.
Add-ons that **change the behavior of or extend existing** XenForo functionality
are more likely to create bugs and conflicts.

XenForo cannot provide support for problems involving or caused by a third-party
add-on — you need to contact the add-on author for guidance. If you have a
problem with XenForo while using third-party add-ons:

1. Disable all add-ons and style customizations and see if you can still
   reproduce the problem.
2. If you **cannot** reproduce it, the problem is likely caused by an add-on or
   customization. Re-enable your add-ons one by one until the problem comes back
   and you've identified the specific cause.
3. If the problem **still** occurs with add-ons and customizations disabled, it
   may be a bug or problem within XenForo itself, which can be worked through via
   a ticket.

### Installing or upgrading an add-on

The process for installing and upgrading an add-on is essentially identical.
There are two methods.

#### Control panel installation (2.1+)

On XenForo 2.1 or newer you may be able to install or upgrade an add-on by
uploading the zip file directly in the control panel. This requires a compatible
server configuration and a change to `src/config.php`.

To enable this feature, first add the following line to **src/config.php**:

```php
$config['enableAddOnArchiveInstaller'] = true;
```

> **Security warning (from the manual):** this feature is **disabled by default
> for security reasons**. If it is enabled and an admin account is compromised,
> it may allow an attacker to execute arbitrary code by uploading it from the
> control panel. You may wish to enable this feature by making the `config.php`
> change **only when you intend to use it**.

Once that change is made:

1. Go to the **Add-ons** section of the control panel and click
   **Install/upgrade from archive**.
2. If your server configuration meets the requirements, a file upload option is
   displayed. Select the add-on(s) you wish to install or upgrade and submit the
   form.
3. Follow the on-screen instructions.

If an error occurs when installing or upgrading via this method, complete the
action by following the manual installation process below.

#### Manual installation

> **Note:** In XenForo 2, all add-ons should have a standardized zip format.
> This process assumes the add-on is in that format.

1. Download the add-on and unzip the file locally. Inside it you will see an
   `upload` directory, just like when XenForo itself was installed. You'll be
   uploading the **contents** of this directory.
2. Using your FTP client, navigate to the XenForo root directory on the server
   and upload the **contents** of the `upload` directory into it. Ensure that
   you **"merge"** with the existing contents on the server. (If upgrading, this
   should overwrite some existing files.)
3. In the control panel, go to the **Add-ons** section. The add-on you just
   uploaded should be listed as installable or upgradeable. Click the relevant
   button and follow the on-screen instructions.

### Disabling an add-on

Disabling an add-on effectively turns it off, similar to it not being installed
in the first place. **Any data created by the add-on remains in the database**
and is accessible when you re-enable it.

- Disable a single add-on by clicking the gear icon and choosing **disable**.
- Disable **all** add-ons quickly using the **"disable all"** link at the top of
  the add-on list. This is often required if you contact support. When all
  add-ons are disabled, an **"enable"** button appears at the top of the list,
  allowing you to quickly re-enable them.

### Uninstalling an add-on

Uninstalling an add-on removes it from your XenForo installation completely.

> **Data loss warning (from the manual):** any data associated with the add-on
> will be removed, and **you will not be able to recover that data after
> uninstalling the add-on.**

To uninstall, choose **uninstall** from the gear icon menu for the add-on. Note
that **the add-on files you uploaded are not removed** when uninstalling — these
must be removed manually via FTP.

| Action | Effect on add-on data | Files removed? |
|---|---|---|
| **Disable** | Data remains in the database; restored on re-enable | No |
| **Uninstall** | Data is removed and cannot be recovered | No — remove manually via FTP |

### Regaining control panel access

If an add-on is preventing you from accessing the control panel or from
disabling add-ons, you can temporarily add the following line to the end of your
`src/config.php` file:

```php
$config['enableListeners'] = false;
```

To do this, download the file via FTP, open it on your computer in a basic text
editor (not a word processor), save the changes, and reupload it to your server.
This temporarily disables code being run by **all** add-ons. Use it to access
the control panel and disable the offending add-ons, then **remove the line
again** from `src/config.php`.

> **Note:** This is **not** equivalent to disabling add-ons via the control
> panel and is **not** sufficient for determining if an issue is caused by an
> add-on.

> **For add-on developers:** the standardized add-on zip format and the
> `upload` directory layout referenced above relate to add-on structure and the
> build/release process — see `docs/02-addon-structure.md` and
> `docs/14-build-release-devtools.md`.

---

## Closing the board

One of the most important options controls whether or not the forums are open to
visitors. The **Board is active** option, in the **Board active** option group,
toggles this.

When the board is **not** active:

- all visitors **apart from** administrators with admin control panel access are
  shown the message you enter in the **Inactive board message** box, and
- visitors **will not be able to view or post any content** while the board is
  not active.

> **Reminder (from the manual):** don't forget to reactivate the board once
> you've finished doing whatever necessitated its closure.

It's usually a good idea to close the forums for essential maintenance
operations, such as:

- running XenForo upgrades,
- importing data, and
- installing large or complex add-ons.

---

## ACP location quick reference

| Task | ACP path |
|---|---|
| Create / manage REST API keys | **Setup > Service providers > API keys** |
| Add a payment profile | **Options > Service providers > Payment profiles > Add payment profile** |
| Edit options | **Setup > Options** |
| Show advanced options | Gears menu footer **Show advanced options**, or the button on the option groups listing page |
| Install / upgrade / disable / uninstall add-ons | **Add-ons** section of the ACP |
| Close the board | **Board is active** option in the **Board active** option group |
