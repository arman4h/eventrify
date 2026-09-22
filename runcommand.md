Run in local machine - 
1 .Xampp start basic apache & mysql 
2 . Open Terminal in VS Code and Paste this "C:\xampp\php\php.exe -S localhost:8000 -t public"
3. vist localhost:8000

user account - arman4hn@gmail.com - arman123
club account - mahi@gmail.com - mahi123
System Admin account - admin@eventrify.com - admin123

Club registration flow:
- Applicant sets their own password during club registration.
- After submitting, the applicant is auto-logged-in and redirected to /club.
- Since the club is 'pending', the dashboard shows an "Account Not Active Yet" screen
  (Pending Review badge + application ID). All other club pages redirect back to /club.
- Once the admin approves the club (status -> 'approved'), the full dashboard unlocks.
- Club user logs in at /club/login using the official club email + chosen password.
