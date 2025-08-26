<?php
function send_email_simple(string $to, string $subject, string $bodyHtml): bool {
  $headers  = "MIME-Version: 1.0\r\n";
  $headers .= "Content-type: text/html; charset=UTF-8\r\n";
  $headers .= "From: OutdoorTribe <no-reply@outdoortribe.local>\r\n";
  return @mail($to, $subject, $bodyHtml, $headers);
}
