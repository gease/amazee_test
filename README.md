Steps to install and run the site:

* Clone locally the repository ``git clone https://github.com/gease/amazee_test.git some_dir``
* Run ``composer install`` in the directory where the repo was cloned.
* Follow normal drupal installation procedure (https://www.drupal.org/docs/user_guide/en/install-run.html)
* Enable module called "Arithmetic".
* Create some content type that has a field of **Text(plain)** type.
* In the **Manage display** tab, select **Calculated value** formatter for that field.
* Enter any calculation formula in this field, eg "_5*(7+3)_". When viewing the content type, the result of calculation will be displayed on hover.

