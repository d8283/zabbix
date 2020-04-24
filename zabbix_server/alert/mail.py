#!/usr/bin/env python
#coding: utf-8
import smtplib
import sys
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
def sendqqmail(receiver,subject,content):	
	sender = 'monitor@daoxila.com'
	#receiver = sys.args[0]
	#subject = sys.args[1]
	smtpserver = 'smtp.exmail.qq.com'
	username = 'monitor@daoxila.com'
	password = 'XdeABuT3aZ80'

	msg=MIMEMultipart(charset='utf-8')
	msg['Subject'] = subject
	msg['From'] = 'System Monitor <monitor@daoxila.com>'
	msg['To'] = receiver
	# print content
	# print subject
	
	text=MIMEText(content,_charset='utf-8')
	msg.attach(text)
	smtp = smtplib.SMTP()
	# print sender
	# print receiver
	# print content
	# print subject
	smtp.connect(smtpserver)
	smtp.login(username, password)
	# print msg.as_string()
	smtp.sendmail(sender, receiver, msg.as_string())
	smtp.quit()
if __name__=="__main__":
    # print("main")
    sendqqmail(sys.argv[1],sys.argv[2],sys.argv[3])
