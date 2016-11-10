This is an Ubercart payment gateway module for QuickPay.

Installation and Setup
======================

a) Download uc_quickpay module from https://drupal.org/projects/.

b) Install and enable the module in the normal way for Drupal.

b) Visit your Ubercart Store Administration page, Configuration
section, and add the QuickPay gate at the Payment Methods page.
(admin/store/config/payment)

c) Configure the gateway with your manage quickpay API keys from  https://manage.quickpay.net/.

d) Every site dealing with credit cards in any way should be using https. It's
your responsibility to make this happen. (Actually, almost every site should
be https everywhere at this time in the web's history.)


Limitations
===========

At this writing, the uc_quickpay refund process in under construction. This is a dev version