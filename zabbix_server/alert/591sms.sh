#!/bin/bash

userid="367"
account="daoxi106"
password="dx106156.com"
url="http://dx.591sms.com/sms.aspx?action=send"

userid="528"
account="daoxibaojing"
password="dxlbj==1688"
url="http://www.591duanxin.com/sms.aspx?action=send"

#$curlPost = 'userid='.$userid.'&account='.$account.'&password='.urlencode($password).'&mobile='.$mobile.'&content='.urlencode($content);
mobile=$1
content="$2"         
echo  $url   -d  userid=$userid -d password=$password -d account=$account -d mobile=$mobile -d content="$content" >> /tmp/sms.log
curl  $url -q -s -d  userid=$userid -d password=$password -d account=$account -d mobile=$mobile -d content="$content"  >> /tmp/smsend.log
