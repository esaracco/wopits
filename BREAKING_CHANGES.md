# Breaking Changes

### 0.64-alpha.0

* Upgrade your DB with the SQL script available in `upgrade/0.64-alpha.0/`.
* Now that external modules are no longer available in the Git tree, you must install them manually using `npm` and `composer` if you run wopits *as is* without deployment. See `app/libs/README.md` and `www/libs/README.md`. However, *no action is required if you use the `deploy` script to deploy the app* (you just need to have `npm` and `composer` installed).
