-- E-mail verification is gone. The app never shipped a real mailer, so the
-- confirmation link only ever reached storage/mail.log — every account created
-- in production stayed locked out for good. Accounts are usable the moment they
-- are created, which leaves both the token table and the column dead weight.
DROP TABLE email_verifications;

ALTER TABLE users DROP COLUMN email_verified_at;
