<?php

declare(strict_types=1);

namespace App\Legal;

final class PrivacyPolicyDefinition
{
    /**
     * Copy sourced from Culver Square developer Figma frame “Culver Square - Privacy Policy”.
     *
     * @return array{hero_title: string, hero_subtitle: string, sections: list<array{aside: string, body: string}>}
     */
    public static function data(): array
    {
        $intro = <<<'HTML'
<p>Blueboys JV (Colchester) Ltd is a limited liability company incorporated in England and Wales (registration number 16372966) and our registered office is 68 Grafton Way, London, W1T.</p>
<p>This Privacy policy explains who we are, how we collect, share and use your personal data, and how you can exercise your privacy rights in relation to this website.</p>
<p>If you have any questions regarding this notice, please contact our Data Protection Officer using the contact details on this page.</p>
HTML;

        $contactAside = <<<'HTML'
<div class="flex flex-col gap-6 font-sans">
  <div>
    <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-deep-moss">By Email</p>
    <p class="m-0"><a href="mailto:info@culversquare.co.uk">info@culversquare.co.uk</a></p>
  </div>
  <hr class="m-0 border-0 border-t border-deep-moss/15" />
  <div>
    <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-deep-moss">By Post</p>
    <p class="m-0 leading-snug">Data Protection Officer,<br />68 Grafton Way,<br />London, W1T</p>
  </div>
</div>
HTML;

        $thirdParty = <<<'HTML'
<p>This Website contains links to and from other websites which are independent of us and are maintained by third parties. We are not responsible for the contents or privacy practices of any third-party websites and shall not be liable for any damages or injury arising from the contents of such websites. Any links to other websites are provided as a convenience to you as a user of this Website, and do not imply our endorsement of the linked websites or association with their operators.</p>
HTML;

        $collect = <<<'HTML'
<p>We may collect personal data about you that you provide to us, when you:</p>
<ul>
<li>Complete an online contact form</li>
<li>Visit our website</li>
</ul>
<p>We collect the following groups of information about you:</p>
<ul>
<li><strong>Identity Data;</strong> this includes but is not limited to first name, last name and date of birth</li>
<li><strong>Contact Data;</strong> this includes email address, telephone number and postal address</li>
<li><strong>Any personal data about you included in free text boxes;</strong> including in relation to enquiries or comments submitted to us</li>
<li>The IP address of the computer or device you are using to access our website (this is a number which recognises the computer or other device used to access the Internet. A web server automatically collects IP addresses and uses them to administer a website)</li>
<li><strong>Technical Data;</strong> this includes internet protocol (IP) address, browser type and version, time zone setting and location, browser plug-in types and versions, operating system and platform and other technology on the devices you use to access this website</li>
<li><strong>Profile Data;</strong> this includes feedback and survey responses</li>
<li><strong>Usage Data;</strong> this includes information about how you use our website or services such as wi-fi (where available)</li>
</ul>
<p>We collect information about the use of the Website and services. Some of this we may automatically collect when you visit the Website such as what type of device or internet browser you are using (“Technical Data”). We also collect information using Cookies. Cookies are small pieces of information stored on your browser which sometimes follow the movements of visitors on the Website and tell us how the Website and our services are being used, this information helps us improve your experience of the Website.</p>
<p>You can set your browser to refuse all or some browser cookies, or to alert you when websites set or access cookies. If you disable or refuse cookies, please note that some parts of this website may become inaccessible or not function properly. For more information on Cookies use please see our Cookie Policy and Cookie Settings.</p>
HTML;

        $howUse = <<<'HTML'
<p>We use your personal data for the following purposes:</p>
<ul>
<li>To provide information which has been requested via an Online contact form or email query</li>
<li>To manage our relationship with you including: important changes to the Website, new services your preferences on what you want to hear from us</li>
<li>To provide you with our public wi-fi service (where available)</li>
<li>To administer and protect our business and this website (including troubleshooting, data analysis, testing, system maintenance, support, reporting and hosting of data)</li>
<li>To use data analytics and to improve our website, services, marketing and consumer experiences</li>
<li>Photography (at in-centre events)</li>
<li>Videography (at in-centre events)</li>
</ul>
HTML;

        $sharing = <<<'HTML'
<p>When necessary; we share your personal data with:</p>
<ul>
<li>Our service providers who support us with providing these services;</li>
<li>Our affiliated companies;</li>
<li>Tax, government and/or regulatory authorities;</li>
<li>Prosecuting authorities and courts, and/or other relevant third parties connected with legal proceedings or claims;</li>
<li>Fraud prevention and/or law enforcement agencies; and</li>
<li>Third parties where we are required to do so by law.</li>
</ul>
<p>The recipients set out above may be based in the European Economic Area (EEA) or in countries outside the EEA.</p>
HTML;

        $transfers = <<<'HTML'
<p>Your personal data can be transferred outside of the UK and EEA from time to time to trusted service providers and other third parties when necessary. When we do this; we require third parties to keep your personal data confidential and secure. We ensure that suitable protection is maintained at all times by ensuring that appropriate safeguards are in place.</p>
<p>Where we are required by law to disclose, we may not always have control over the terms under which we are required to share your personal data. We will make sure that any disclosure is lawful.</p>
HTML;

        $retain = <<<'HTML'
<p>We retain your personal data for as long as is necessary for the purposes described above. We keep personal data for as long as it is required by us to meet our legal or regulatory obligations and in the defence of any legal claims.</p>
HTML;

        $automated = <<<'HTML'
<p>We do not use any automated processes to make decisions about you which produce legal or significant effects concerning you.</p>
HTML;

        $rights = <<<'HTML'
<p>You have certain rights relating to the personal data we hold about you which are outlined below:</p>
<ul>
<li><strong>Request access to the personal data we hold about you (Data Access Request):</strong> You may request access to a copy of the personal data we hold about you. We can refuse to provide personal data where to do so may reveal another person’s personal data or would otherwise negatively impact another person’s rights.</li>
<li><strong>Object to processing (Right to Object)</strong> You may object to us undertaking automated processes, or fully automating decision making, using your personal data except where used to detect, prevent and investigate fraud and other financial crimes. You may also object to us using your personal data for direct marketing purposes. This includes any profiling we perform as part of our direct marketing activities. Once we receive and have processed your objection, we will stop using your personal data for these purposes.</li>
<li><strong>Request a copy of your personal data (Data Portability)</strong> Where you gave us the personal data directly and it was processed electronically, you can request the data we hold on you in a commonly used machine-readable format.</li>
<li><strong>Request that your personal data is deleted (Right to be Forgotten)</strong> You can ask us to delete the personal data we hold about you when it is no longer required for a legitimate business need, legal or regulatory obligations, where you have withdrawn your consent or for the purposes it was collected for.</li>
<li><strong>Amend or correct your personal data (Right to Rectification)</strong> If you believe that the personal data we hold about you is inaccurate, incorrect or incomplete, please contact us as soon as possible so we can update it.</li>
<li><strong>Restrict the processing of your personal data (Right to Restrict)</strong> You may ask us to restrict our processing of your personal data whilst we resolve any complaints you have about the way your data is used, require it for a legal claim, believe the personal data is not accurate, we no longer need the data, you have objected to the processing of your personal data or if you think our processing is unlawful but you do not want us to delete your data.</li>
<li><strong>Rights in relation to consent (Right to Withdraw)</strong> At any time, you may withdraw the consent you granted for your personal data to be used for direct marketing. When you withdraw your consent, it will not affect the lawfulness of any past activities we have undertaken based on the previous consent.</li>
</ul>
HTML;

        $complaint = <<<'HTML'
<p>If you have any concerns about the use of your personal data, or the way we handle your requests relating to your rights, you can raise a complaint directly with us by contacting our Data Protection Officer using the details below:</p>
<p><strong>By post:</strong> Data Protection Officer, 68 Grafton Way, London, W1T<br /><strong>By email:</strong> <a href="mailto:info@culversquare.co.uk">info@culversquare.co.uk</a></p>
<p>If you are not satisfied with the way we handle your complaint, you are entitled to raise a complaint directly with the UK Information commissioner’s Office via the details available on their website: <a href="https://www.ico.org.uk" rel="noopener noreferrer" target="_blank">www.ico.org.uk</a>.</p>
<p>For alternate EU Data Protection Authority contacts please see further information on the following link National Data Protection Authorities.</p>
HTML;

        return [
            'hero_title' => __('Privacy Policy.', 'culvers'),
            'hero_subtitle' => __("We're committed to respecting your privacy", 'culvers'),
            'sections' => [
                [
                    'aside' => $contactAside,
                    'body' => $intro,
                ],
                [
                    'aside' => '<p>' . esc_html__('Third Party Websites', 'culvers') . '</p>',
                    'body' => $thirdParty,
                ],
                [
                    'aside' => '<p>' . esc_html__('What Personal Data We Collect from You', 'culvers') . '</p>',
                    'body' => $collect,
                ],
                [
                    'aside' => '<p>' . esc_html__('How We Use Your Personal Data', 'culvers') . '</p>',
                    'body' => $howUse,
                ],
                [
                    'aside' => '<p>' . esc_html__('Sharing Your Personal Data', 'culvers') . '</p>',
                    'body' => $sharing,
                ],
                [
                    'aside' => '<p>' . esc_html__('Transfers of Your Personal Data Outside the UK and EEA', 'culvers') . '</p>',
                    'body' => $transfers,
                ],
                [
                    'aside' => '<p>' . esc_html__('Retaining your personal data', 'culvers') . '</p>',
                    'body' => $retain,
                ],
                [
                    'aside' => '<p>' . esc_html__('Automated Decision Making', 'culvers') . '</p>',
                    'body' => $automated,
                ],
                [
                    'aside' => '<p>' . esc_html__('Your Data Subject Rights', 'culvers') . '</p>',
                    'body' => $rights,
                ],
                [
                    'aside' => '<p>' . esc_html__('Making A Data Protection Complaint', 'culvers') . '</p>',
                    'body' => $complaint,
                ],
            ],
        ];
    }
}
