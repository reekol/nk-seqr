## SeQR text storage, encryptiion, sharing tool.

### Live demo.
- Url: https://seqr.link

- Attention: it uses a self signed certifcate.

### To install dependencies
```bash
apt-get install zbar-tools qrencode wamerican
```
### Default credentials
- Basic auth: admin/admn123

### Safety

- The safest method is to provide password. Then server creaates a salt to it and image can be decrypted only combining both of then, and can be invalidated when salt is deleted.

- OTP method ( TOTP ) is less safe, because it's seed exists in both, server and cient.

- Passowrd prefixed with "RAW:" encrypts your message without salt and can't be invalidated.
