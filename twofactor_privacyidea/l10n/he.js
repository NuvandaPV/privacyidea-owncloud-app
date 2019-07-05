OC.L10N.register(
    "twofactor_privacyidea",
    {
    "Communication to the privacyIDEA server succeeded. The user was successfully authenticated." : "התקשורת לשרת ה- privacyIDEA נוצרה. המשתמש אומת בהצלחה.",
    "Failed to authenticate." : "נכשל באימות.",
    "Communication to the privacyIDEA server succeeded. However, the user failed to authenticate." : "התקשורת לשרת ה- privacyIDEA נוצרה בהצלחה. אבל, המשתמש נכשל באימות.",
    "The service account credentials are correct!" : "אישורי האימות של חשבון השירות נכונים!",
    "Failed to trigger challenges. Wrong HTTP return code: " : "הפעלת אתגרי האימות נכשלה. קוד HTTP חוזר שגוי:",
    "Failed to trigger challenges." : "נכשל ניסיון לעורר אתגרים.",
    "Please confirm the authentication with your mobile device or enter a valid otp." : "יש לאשר את האימות באמצעות המכשיר הנייד שלך או להזין קוד חד פעמי חוקי.",
    "Check if service account has correct permissions" : "יש לבדוק אם לחשבון השירות יש הרשאות מתאימות",
    "Failed to fetch authentication token. Wrong HTTP return code: " : "נכשל באחזור חתימת ייצוג מאומתת. קוד HTTP חוזר שגוי:",
    "Failed to fetch authentication token." : "נכשל הניסיון לאחזר מחרוזת אימות.",
    "privacyIDEA 2FA" : "privacyIDEA 2FA",
    "Open documentation" : "תיעוד פתוח",
    "\n                In a second step of authentication the user is asked to provide a one\n                time password. The users devices are managed in privacyIDEA. The\n                authentication request is forwarded to privacyIDEA.\n            " : "\n                בשלב שני של האימות המשתמש נדרש לספק סיסמא\n                חד פעמית. התקני המשתמש מנוהלים ב- privacyIDEA. \n                לפיכך בקשת האימות הועברה ל- privacyIDEA.\n            ",
    "Configuration" : "הגדרות",
    "Activate two factor authentication with privacyIDEA." : "הפעלת אימות דו שלבי באמצעות privacyIDEA.",
    "\n                            Before activating two factor authentication with privacyIDEA, please asure, that the connection to\n                            your privacyIDEA-server is configured correctly.\n                        " : "\n                            לפני הפעלת אימות דו שלבי באמצעות privacyIDEA, יש לוודא, שהחיבור לשרת\n                            ה- privacyIDEA שלך מוגדר נכון.\n                        ",
    "URL of the privacyIDEA Server" : "נתיב לשרת ה- privacyIDEA",
    "\n                            Please use the base URL of your privacyIDEA instance.\n                            For compatibility reasons, you may also specify the URL of the /validate/check endpoint.\n                        " : "\n                            יש להשתמש בנתיב הבסיסי של מופע ה- privacyIDEA שלך.\n                            מסיבות תאימות, ניתן גם לציין את נתיב נקודת הקצה לאימות/בדיקה.\n                        ",
    "Timeout" : "פסק זמן",
    "default is 5" : "ברירת מחדל הנה 5",
    "\n                            Sets timeout to privacyIDEA for login in seconds.\n                        " : "\n                            מגדיר פסק זמן להתחברות ל- privacyIDEA בשניות.\n                        ",
    "Include groups" : "כולל קבוצות",
    "Exclude groups" : "לא כולל קבוצות",
    "\n\t\t                    If include is selected, just the groups in this field need to do 2FA.\n\t\t                " : "\n\t\t                    אם נבחר כולל קבוצות, רק הקבוצות בשדה זה צריכות לעבוד עם 2FA.\n\t\t                ",
    "\n\t\t                    If you select exclude, these groups can use 1FA (all others need 2FA).\n\t\t                " : "\n\t\t                    אם נבחר לא כולל קבוצות, קבוצות אלו יכולות לעבוד עם 1FA (כל האחרות צריכות לעבוד עם 2FA).\n\t\t                ",
    "\n\t\t                    Exclude ip addresses\n\t\t                " : "\n\t\t                    לא לכלול כתובות ip\n\t\t                ",
    "\n\t\t                    You can either add single IPs like 10.0.1.12,10.0.1.13, a range like 10.0.1.12-10.0.1.113 or combinations like 10.0.1.12-10.0.1.113,192.168.0.15\n\t\t                " : "\n\t\t                    ניתן להוסיף כתובות IP בודדים כדוגמת 10.0.1.12,10.0.1.13, תחום כדוגמת 10.0.1.12-10.0.1.113 או שילוב כדוגמת 10.0.1.12-10.0.1.113,192.168.0.15\n\t\t                ",
    "User Realm" : "משתמש Realm",
    "\n                    Select the user realm, if it is not the default one.\n                " : "\n                    בחירת משתמש realm, אם זה אינו ברירת המחדל.\n                ",
    "\n                    Verify the SSL certificate.\n                " : "\n                    מאמת תעודת SSL.\n                ",
    "\n                        Do not uncheck this in productive environments!\n                    " : "\n                        אל תבטל את הסימון בסביבות פרודוקטיביות!\n                    ",
    "\n                    Ignore the system wide proxy settings and send authentication\n                    requests to privacyIDEA directly.\n                " : "\n                    התעלם מהגדרות המערכת הרחבות של הפרוקסי ושלח בקשות\n                    אימות ל- privacyIDEA באופן ישיר.\n                ",
    "Test" : "בדיקה",
    "Test Authentication by supplying username and password that are checked against privacyIDEA:" : "בודק אימות על ידי הזנת שם משתמש וסיסמא שנבדקים מול privacyIDEA:",
    "User" : "שתמש",
    "Password" : "סיסמא",
    "Challenge Response" : "תגובת אתגר",
    "Trigger challenges for challenge-response tokens. Check this if you employ, e.g., SMS or E-Mail tokens." : "הפעלת אתגרים עבור תגובת אתגר של חתימות ייצוג. בודק אם ברשותך חתימות ייצוג עבור, SMS או דואר אלקטרוני, לדוגמא.",
    "Username of privacyIDEA service account" : "שם משתמש עבור חשבון שירות privacyIDEA",
    "Password of privacyIDEA service account" : "סיסמא עבור חשבון שירות privacyIDEA",
    "Check Credentials" : "בדיקת אישורי אימות"
},
"nplurals=4; plural=(n == 1 && n % 1 == 0) ? 0 : (n == 2 && n % 1 == 0) ? 1: (n % 10 == 0 && n % 1 == 0 && n > 10) ? 2 : 3;");
