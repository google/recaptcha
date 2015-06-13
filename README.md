# recaptchaDemo-Streamlined
This is the same demo provided by Google for Recaptcha, minus eight vendor libraries that are not used in the Google demo, but are linked in unnecessarly by their Composer autoloader.  This demo performs exactly the same as the Google demo, but just leaves out the unused files they included via their Composer setup.

To see this demo working, please visit http://launchpad-001.com/_firingRange/googrecapmaster/

This program is not recommended for developers who need to maximize the number of unused files within their libraries.  This demo has 12 files.  Developers wanting to include Doctrine, Symfony, and other libraries that increase the number of files in their projects, without the risk of any of those files affecting their projects should download google/recaptcha instead.
