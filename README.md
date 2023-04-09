# ShareX Upload Script

This is a PHP script that lets you easily upload screenshots taken with the ShareX app to your website. ShareX is a popular screenshot tool for Windows that allows you to take screenshots, annotate them, and share them online.

To use this script, you need to upload it to your website and configure it with a secret key. Once that's done, you can configure ShareX to use your script as the upload target. This will let you quickly and easily upload your screenshots to your website without having to manually upload them one by one.

The script will check if the secret key is correct, if the file was uploaded correctly, and will convert images to the webp format if they are in a supported format such as png or jpeg. If everything is okay, the script will return a JSON response containing a URL to the uploaded file on your website. If there's an error, it will return an error message.
