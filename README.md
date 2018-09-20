# Simple PHP API

A couple of months back, a site I maintain for some friends asked me if I could set up an API
for them, so they could check the results of a specific DB table and query for 
results in it, and update/delete from it as necessary.

This is just a simple PHP-based API I whipped up real quick one afternoon to accommodate this request.

I've reworked the (admittedly hastily thrown together) original into a reusable class.
The DB pulls from MySQL using PDO, and redirects are setup in the .htaccess file
to give it more friendly URLs for the end users to use. The guys using this API already had their
own method for accessing the API once it was available, so I didn't have to worry about anything 
on their end, just set up the API.

Here is the DB table in question being used for this example:

````
CREATE TABLE `treasurehunt_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `target` varchar(255) DEFAULT NULL,
  `object` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
)
