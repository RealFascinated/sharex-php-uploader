# ShareX Upload Script

This PHP script allows you to effortlessly upload your ShareX app screenshots to your website. ShareX is a widely-used Windows screenshot tool that enables users to take screenshots, annotate them, and share them online.

## Features

- Effortlessly upload screenshots to your website using ShareX.
- Automatically convert screenshots to WebP format.

## Installation

To install this script:

- Find a suitable location to host the script (with at least 5GB of storage).
- Verify that your web server has the GD library installed. (See instructions below for Ubuntu installation.)
- Upload the files to your server.
- Edit the `upload.php` file to configure the script.
- Add the custom uploader to ShareX.
- Configure the custom uploader in ShareX.
- Test the script!

## Need Help?

If you require assistance, feel free to contact me via Discord at `fascinated7`.

## How to Install GD Library on Ubuntu

- `sudo apt-get install php-gd`
- Add extension=gd to your php.ini file.
- Restart your web server and php.

## Other Information

This project is maintained on my Gitea instance: <https://git.fascinated.cc/Fascinated/sharex-php-uploader>
