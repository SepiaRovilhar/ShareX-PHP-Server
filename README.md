# ShareX-Server
ShareX Server is an open-source web application for uploading and sharing images, files, and text.
### This project is not affiliated with ShareX

## Features
- Upload images, files and text
- View images, music, videos and text

## Requirements
- PHP 7.2 or higher

## Installation
1. Clone the repository
2. Edit the `config.php` file on config folder. Don't forget to change the token.

## Configuration
- You can see full configuration on this image: https://cdn.mathysl.xyz/zyewuOtzCfvH.png
* On name, you can put your custom name for your server
* On destination type, you can select ``Image Uploader``, ``Text Uploader`` and ``File Uploader``
* On method, you select ``POST``
* On request URL, you put your domain name (ex: https://example.com/ or https://cdn.example.com/)
* On Body select ``Form data (multipart/form-data)``
* On Headers put in name ``Authorization`` and in Value ``Bearer YOUR_TOKEN`` define on config.php
* On File form name, you put ``file``
* On URL form name, you put ``{json:data}``
* On Deletion URL, you put ``{json:delete}``
* On error message, you put ``{json:error}``
* Now you can save your configuration and test it

## Usage
- Acctualy, the server is made for running without specification of api.php on url. (for acces to a file use https://example.com/file)
## License
[GNU GPLv3](https://choosealicense.com/licenses/gpl-3.0/)

## Support
- [GitHub Issues](https://github.com/SepiaRovilhar/ShareX-Server/issues)
- Discord: ``SepiaRovilhar#3003``

## Disclaimer
- This project not contain admin panel, if you want to delete a file, you need to use URL given.
- The project owner is not responsible for any illegal use of this project.
- The project owner is not responsible for any damage caused by this project.
- The project owner is not responsible for any damage caused by the use of this project.
## Warning
- This project is still in development, so it may have bugs and errors.
- For critical bug, contact me on [email](mailto:mathys@mathysl.xyz): ``mathys@mathysl.xyz``