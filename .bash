# 1. Upload files to server
# 2. Set permissions
chmod 755 uploads/
chmod 644 app/config/config.php

# 3. Run installation script
# Visit: yourdomain.com/install.php

# 4. Remove install files (for security)
rm install.php
rm database/schema.sql