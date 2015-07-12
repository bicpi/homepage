---
debug: true

recaptcha_secret: {{recaptcha_secret|default('~')}}

mailer_host: {{mailer_host|default('locahost')}}
mailer_port: {{mailer_port|default(25)}}
mailer_username: {{mailer_username|default('')}}
mailer_password: {{mailer_password|default('')}}
mailer_encryption: {{mailer_encryption|default('~')}}
mailer_auth_mode: {{mailer_auth_mode|default('~')}}
