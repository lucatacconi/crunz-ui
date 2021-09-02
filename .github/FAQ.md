# FAQ and Troubleshooting

Below are a number of useful tips for configuring the system and for solving common problems. If the problem you encountered is not reported please contact us and open an issue.

### In the initial check on the dashboard I am reported configuration errors

When accessing the Crunz-ui dashboard, Crunz-ui checks the system status. Verify that tasks and logs folder is present and writable. Then check that the Crunz configuration file is present and correctly configured.

In the event of an error relating to the log and task folders, the system requests that the folders be present and writable.
For example with apache server on Ubuntu for example configure folders with user and group www-data:
```
cd /var/www/html
sudo chown -R www-data:www-data crunz-ui
```

If Crunz is not configured, run the initial configuration batch (the procedure will produce the configuration file crunz.yml).
```
cd /var/www/html/crunz-ui
sudo ./vendor/bin/crunz publish:config
```

> :warning: ***Attention, the above examples work on an embedded Crunz installation***


### I use Xampp on my server and I get an error when I try to manually execute a task


### How do I configure the system to generate logs in a custom folder?

### My server is very slow. I can do something to make the interfaces more responsive?

### I already have Crunz installed on my server. How do I configure Crunz-ui?

### After successful login the system returns to the login page
