# Apache2 Honeypot

A basic Apache2 honeypot that leverages the capabilities of `/dev/urandom`, `PHP`, and `.htaccess` to generate random fake content for every URL.

## Features
- **Random page generation:** For every URL accessed, a unique random page is generated using `/dev/urandom`.
- **User isolation with mpm-itk:** Ensures that the honeypot runs under a separate user for additional security.
- **Simple setup:** Uses standard Apache modules and PHP, with minimal configuration.
- **Access logging:** Logs all requests and errors for monitoring and analysis.

## Installation

1. **Clone the repository:**

   ```bash
   git clone https://github.com/ycomiti/apache2-honeypot.git
   ```

2. **Install Apache2 and the required modules:**

   Ensure that Apache is installed along with the `mpm-itk` module. You can install it using:

   ```bash
   sudo apt install apache2 libapache2-mpm-itk
   ```

3. **Configure Apache2:**

   Copy the provided `honeypot.conf` to your Apache `sites-available` directory:

   ```bash
   sudo cp apache2/honeypot.conf /etc/apache2/sites-available/honeypot.conf
   ```

4. **Enable the site and necessary modules:**

   Enable the honeypot site and the required Apache modules (`rewrite` and `mpm-itk`):

   ```bash
   sudo a2ensite honeypot.conf
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

5. **Set up the honeypot directory:**

   Ensure that the `DocumentRoot` directory specified in `honeypot.conf` exists and contains the files from the `site` folder:

   ```bash
   sudo mkdir -p /var/www/honeypot
   sudo cp -r site/* /var/www/honeypot/
   sudo chown -R honeypot:honeypot /var/www/honeypot
   ```

6. **Set proper permissions:**

   ```bash
   sudo chmod -R 700 /var/www/honeypot
   ```

## mpm-itk Isolation

In the `honeypot.conf` configuration file, the `mpm-itk` module is used to run the honeypot under a separate user (`honeypot`), providing user isolation for increased security. This prevents the honeypot from potentially compromising other services running on the same server.

Here’s the relevant section in the `honeypot.conf` file:

```apache
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/honeypot

    <IfModule mpm_itk.c>
        AssignUserID honeypot honeypot
    </IfModule>

    <Directory /var/www/honeypot>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog /var/www/honeypot/error.log
    CustomLog /var/www/honeypot/access.log combined
</VirtualHost>
```

### Explanation:
- **mpm-itk:** The `mpm-itk` module allows Apache to run each virtual host under a separate user and group, in this case, the `honeypot` user and group. This enhances security by isolating the honeypot from the rest of the system.

  ```apache
  <IfModule mpm_itk.c>
      AssignUserID honeypot honeypot
  </IfModule>
  ```

  By doing this, even if an attacker exploits the honeypot, they will only have access to resources associated with the `honeypot` user, reducing the risk of compromising the entire system.

## How It Works

- **`.htaccess`:**
  The `.htaccess` file rewrites all non-existent files and directories to `index.php`, which serves random content.

  ```apache
  RewriteEngine On
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule ^.*$ /index.php [L,QSA]
  ```

- **`index.php`:**
  This file uses `/dev/urandom` to generate random binary data each time a URL is accessed. The length of the random data is determined by a random number between 1 and 10,000 bytes. If the `/dev/urandom` file cannot be opened, an error message is shown if debugging mode is enabled.

  ```php
  <?php
    DEFINE("DEBUG", false);

    $file = fopen('/dev/urandom', 'rb');

    if ($file) {
      $randomData = fread($file, rand(1, 10000));
      echo $randomData;
      fclose($file);
    } else {
      if (DEBUG) {
        echo "Failed to open urandom.";
      }
    }
  ?>
  ```

  ### Explanation:
  - **`fopen('/dev/urandom', 'rb')`:** Opens the `/dev/urandom` device file in read-binary mode.
  - **`fread($file, rand(1, 10000))`:** Reads a random number of bytes (between 1 and 10,000) from `/dev/urandom`.
  - **Debugging mode:** If the file cannot be opened, and `DEBUG` is set to `true`, an error message will be displayed. This can be useful for troubleshooting.

## Logs

All access and error logs are saved in the `/var/www/honeypot/` directory:

- **Error Log:** `/var/www/honeypot/error.log`
- **Access Log:** `/var/www/honeypot/access.log`

These logs are useful for monitoring traffic to the honeypot, identifying potential attacks, and analyzing suspicious behavior.

## License

This project is licensed under the GPL-3.0 License - see the [LICENSE](LICENSE) file for details.
