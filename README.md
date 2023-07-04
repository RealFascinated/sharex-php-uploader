# ShareX Upload Script

This PHP script allows you to easily upload ShareX screenshots and videos. It includes token authentication for security and can handle various image file types by converting them to WebP format for efficient storage.

## Features

- Effortlessly upload screenshots to your website using ShareX.
- Automatically convert screenshots to WebP format.

## Requirements

- Docker
- ShareX

## Installation

1. Copy the `docker-compose.yml` file to your server.
2. Edit the `docker-compose.yml` file and change `MAX_UPLOAD_SIZE` to the maximum file size you want to allow, and update `./uploads` to where you want to store the files.
3. Run `docker-compose up -d` to start the container.
4. Go to where the files are stored and edit the variables in `upload.php` to your liking.
5. Run `docker-compose restart` to restart the container.
6. Go to Post Installation to configure ShareX.

## Installation (Unraid)

1. Install the container from Community Applications and then edit the variables in the container.
2. Go to where the files are stored and edit the variables in `upload.php` to your liking.
3. Restart the container.
4. Go to Post Installation to configure ShareX.

## Installation (Without Docker - Ubuntu)

This installation method is not recommended as I cannot provide instructions for every single Linux distribution. If you don't know what you're doing, use the Docker installation method.

1. Verify that your web server has the GD library installed. If not, run `sudo apt install php-gd` to install it.
2. Upload the `upload.php` file to your server.
3. Edit the `upload.php` file to configure the script.
4. Go to Post Installation to configure ShareX.

## Post Installation

1. Open ShareX and go to Destinations > Custom Uploader Settings.
2. Click on Import > From URL and enter `https://git.fascinated.cc/Fascinated/sharex-php-uploader/raw/branch/master/sharex.sxcu`.
3. Edit the URL to your website's URL.
4. Click on Test after you've edited the URL and it should return a URL. If it doesn't, check your settings or contact me.

## Need Help?

If you require assistance, feel free to contact me via Discord at `fascinated7`.

## Other Information

This project is maintained on my Gitea instance: <https://git.fascinated.cc/Fascinated/sharex-php-uploader>
