#
# A python 2 script to check disk space using 'df -h' command and send alert via sendgrid
#

import subprocess
import httplib
import json

threshold = 90
partition = "/"
SENDGRID_KEY = "SG.xxxxxxxxxxxxxxxxxxxxxx.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"

def alertMail(mailTo, subject, message):
  baseurl = "api.sendgrid.com"
  headers = {
    "Content-type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer " + SENDGRID_KEY
  }
  data = json.dumps({
    "personalizations": [ {"to": [{"email": mailTo}]} ],
    "from": {"email": "robolog@example.com"},
    "subject": subject,
    "content": [{"type": "text/plain", "value": message}]
  })
  conn = httplib.HTTPSConnection(baseurl)
  conn.request("POST","/v3/mail/send", data, headers)
  resp = conn.getresponse()
  return resp.status

def check_once():
  df = subprocess.Popen(["df","-h"], stdout=subprocess.PIPE)

  alerts = ""
  for line in df.stdout:
    splitline = line.decode().split()
    if splitline[5] == partition:
      if int(splitline[4][:-1]) > threshold:
        alerts = alerts + "Partition {} using over {}%\n".format(partition, threshold)

  if (alerts != ""):
    alertMail("robolog@example.com", "[ALERT] Disk space low on server SERVER_NAME", alerts)

check_once()
