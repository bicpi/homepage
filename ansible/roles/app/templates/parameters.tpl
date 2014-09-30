---
debug: true

mailer_host: {{mailer_host|default('locahost')}}
mailer_port: {{mailer_port|default(25)}}
mailer_username: {{mailer_username|default('')}}
mailer_password: {{mailer_password|default('')}}
mailer_encryption: {{mailer_encryption|default('~')}}
mailer_auth_type: {{mailer_auth_type|default('~')}}

recaptcha_public_key: {{recaptcha_public_key|default('~')}}
recaptcha_private_key: {{recaptcha_private_key|default('~')}}
