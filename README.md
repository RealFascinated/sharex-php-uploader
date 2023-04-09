# ShareX Upload Script

This is a PHP script that lets you easily upload screenshots taken with the ShareX app to your website. ShareX is a popular screenshot tool for Windows that allows you to take screenshots, annotate them, and share them online.

## Features

- Uploads screenshots to your website using ShareX.
- Can automatically convert screenshots to WebP format.

## Installation

1. Find somewhere to host the script (Preferably more than 5GB of storage).
2. Check that your web server has the GD library installed. (See below for instructions on how to install it on Ubuntu.)
3. Upload the files to your server.
4. Edit the `upload.php` file to configure the script.
5. Add the custom uploader to ShareX.
6. Configure the custom uploader in ShareX.
7. Test it!

## Help

If you need help, you can contact me on Discord at `Fascinated#7668`.

## How to install GD Library on Ubuntu

1. `sudo apt-get install php-gd`
2. Add `extension=gd` to your php.ini file.
3. Restart your web server and php.

## Other

This project is worked on at my Gitea instance: <https://git.fascinated.cc/Fascinated/sharex-php-uploader>
