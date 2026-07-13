<?php

namespace Database\Seeders;

use App\Helpers\SettingHelper;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class PrivacyTermsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->content() as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        SettingHelper::clearCache();
    }

    /**
     * @return array<string, string>
     */
    protected function content(): array
    {
        return [
            'privacy_policy' => <<<'TEXT'
ZirehCargo Privacy Policy

Last updated: July 13, 2026

ZirehCargo ("we", "us", or "our") operates a cargo and cross-border shopping service that helps customers order products from China marketplaces and ship them to warehouses in Tajikistan. This Privacy Policy explains how we collect, use, store, and protect your personal information when you use the ZirehCargo mobile application and related services.

1. Information We Collect
We may collect:
- Account details such as name, phone number, email address, preferred language, and profile photo
- Delivery and warehouse preferences, including your selected Tajikistan warehouse and shipping addresses
- Order information, including products, tracking numbers, parcel measurements, payment status, and order history
- Wallet and payment-related records needed to process deposits, order payments, and pickup fees
- Device information such as device tokens used for notifications and authentication sessions
- Support communications you send to ZirehCargo

2. How We Use Your Information
We use your information to:
- Create and manage your ZirehCargo account
- Process orders, shipping, warehouse receiving, and customer pickup
- Calculate shipping and pickup fees and manage wallet transactions
- Communicate order status updates and service notices
- Improve security, prevent fraud, and comply with legal obligations
- Provide customer support

3. Sharing of Information
We may share limited information with:
- Logistics and warehouse partners in China and Tajikistan as needed to fulfill your orders
- Payment and infrastructure providers that help us operate ZirehCargo securely
- Authorities when required by law

We do not sell your personal information.

4. Data Retention
We retain account, order, and payment records for as long as needed to provide the service, meet legal and accounting requirements, and resolve disputes. If you delete your account, we permanently remove your account profile and related access credentials, subject to records we must keep for legal or operational reasons.

5. Your Rights
Depending on applicable law, you may request access to, correction of, or deletion of your personal information. You can delete your ZirehCargo account from the mobile app or by contacting support. After deletion, your account access is revoked and your profile data is removed according to this policy.

6. Security
We use reasonable technical and organizational measures to protect your information. No method of transmission or storage is completely secure, so we cannot guarantee absolute security.

7. Children's Privacy
ZirehCargo is intended for users who can lawfully create an account and place orders. We do not knowingly collect personal information from children where prohibited by law.

8. Changes to This Policy
We may update this Privacy Policy from time to time. The updated version will be published in the ZirehCargo app and on our public privacy policy page.

9. Contact
If you have questions about this Privacy Policy or your personal data, contact ZirehCargo support through the mobile application or the contact channels published by ZirehCargo.
TEXT,
            'terms_conditions' => <<<'TEXT'
ZirehCargo Terms & Conditions

Last updated: July 13, 2026

These Terms & Conditions ("Terms") govern your access to and use of the ZirehCargo mobile application and services. By creating an account or using ZirehCargo, you agree to these Terms.

1. About ZirehCargo
ZirehCargo provides a platform for customers to browse and order products from supported China marketplaces, arrange cargo shipping, and receive parcels at designated warehouses in Tajikistan for customer pickup.

2. Accounts
- You must provide accurate registration information and keep your account details up to date.
- You are responsible for activity under your account and for keeping your login credentials and device secure.
- ZirehCargo may suspend or terminate accounts that violate these Terms, misuse the service, or present security or fraud risks.

3. Orders and Shipping
- Product availability, pricing, and marketplace details are provided through connected platforms and may change.
- Shipping timelines depend on warehouse processing, carriers, customs, and destination warehouse operations.
- You are responsible for providing correct delivery preferences, warehouse selection, and contact details.
- Parcel tracking, warehouse status updates, and pickup readiness are shown in the ZirehCargo app when available.

4. Fees, Wallet, and Payments
- Order totals may include product cost, cargo shipping, commission, pickup shipping, and other applicable fees.
- Wallet deposits and payments are used to settle eligible ZirehCargo charges.
- Pickup shipping may be calculated from package weight and dimensions using configured shipping rates.
- Fees shown in the app before confirmation are estimates or calculated amounts based on available data and may be updated when measurements or shipping method details are finalized.

5. Customer Pickup
- When an order is ready for pickup at your assigned Tajikistan warehouse, you must follow the pickup instructions in the app.
- You may be required to present a pickup QR code or token and complete any unpaid pickup charges before delivery of the parcel.
- Failure to collect parcels within a reasonable period may result in storage fees or disposition according to warehouse policy and applicable law.

6. Acceptable Use
You agree not to:
- Use ZirehCargo for unlawful goods or prohibited activities
- Interfere with the app, APIs, or warehouse operations
- Attempt unauthorized access to accounts, systems, or data
- Submit false order, payment, or identity information

7. Intellectual Property
The ZirehCargo name, logos, app design, and related materials are owned by ZirehCargo or its licensors. You may not copy or reuse them without permission.

8. Disclaimers
ZirehCargo provides the service on an "as available" basis. Marketplace product quality, seller performance, transit delays, and customs decisions are outside ZirehCargo's full control. To the fullest extent permitted by law, ZirehCargo is not liable for indirect, incidental, or consequential damages arising from use of the service.

9. Limitation of Liability
ZirehCargo's total liability for any claim related to the service is limited to the fees you paid to ZirehCargo for the specific order or transaction giving rise to the claim, except where liability cannot be limited by law.

10. Account Deletion
You may delete your ZirehCargo account at any time from the app. Deletion permanently removes your account access and associated profile data as described in the Privacy Policy and Delete Account information.

11. Changes to These Terms
We may update these Terms from time to time. Continued use of ZirehCargo after changes become effective means you accept the updated Terms.

12. Contact
For questions about these Terms, contact ZirehCargo support through the mobile application or the contact channels published by ZirehCargo.
TEXT,
            'delete_account' => <<<'TEXT'
Delete Your ZirehCargo Account

Last updated: July 13, 2026

You can permanently delete your ZirehCargo customer account from the mobile app.

How to delete your account
1. Open the ZirehCargo mobile application and sign in.
2. Go to your profile or account settings.
3. Choose Delete Account and confirm the request.
4. Once confirmed, your account is permanently deleted and all active login sessions are revoked.

What is deleted
- Your ZirehCargo profile (name, phone, email, preferred language, and profile photo)
- Authentication tokens and account access
- Related customer data that is stored with your account and can be removed under our Privacy Policy

Important notes
- Account deletion cannot be undone.
- You will need to register again if you want to use ZirehCargo in the future.
- Some order, payment, or warehouse records may be retained where required for legal, accounting, logistics, or dispute-resolution purposes.
- If you cannot access the app, contact ZirehCargo support and request account deletion with the phone number registered on your account.

By deleting your account, you acknowledge that your ZirehCargo access will end immediately after confirmation.
TEXT,
        ];
    }
}
