<?php

declare(strict_types=1);

namespace App\Legal;

final class TermsPolicyDefinition
{
    /**
     * @return array{hero_title: string, hero_subtitle: string, sections: list<array{aside: string, body: string}>}
     */
    public static function data(): array
    {
        $intro = <<<'HTML'
<p><a href="https://www.culversquare.co.uk">www.culversquare.co.uk</a> (the “Website”) is owned by Blueboys JV (Colchester) Ltd (“we” or “us” in these terms and conditions) and whose addresses and contact details are set out below and is managed on our behalf by Khalbros Limited, whose details are also set out below. Any personal information you supply to us will be used in the manner set out in our Privacy and Cookies Policy. Please make sure you read our Privacy and Cookies Policy to learn about the information that we collect on the Website and how we process it.</p>
<p>The use of and access to the pages of the Website are subject to the terms and conditions set out below and our Privacy and Cookies Policy (together these “Terms”). Before accessing and using the Website, please read these Terms carefully because they constitute a legal agreement between us and you. By using the Website, you agree to these Terms and affirm that you are able and legally competent to do so. If you do not agree, you are not authorised to use this Website and you should exit the Website immediately.</p>
<p><strong>IMPORTANT NOTE:</strong> These Terms contain provisions that limit our liability to you. Please see “DISCLAIMERS &amp; LIMITATIONS OF LIABILITY” (Section 6) below for more information.</p>
HTML;

        $s1 = <<<'HTML'
<p>The Effective Date of these Terms is set forth at the top of the page. We do not intend to change these Terms very often but reserve the right to do so. Such modifications shall be effective immediately upon posting of the modified terms on the Website. Accordingly, your continued use of this Website constitutes your acceptance of the amended Terms. The amended Terms supersede all previous versions of the Terms.</p>
<p>In addition to these Terms, there may also be specific and additional terms that apply to certain sections of this Website. Because those specific and additional provisions also apply to your use of those sections, we recommend that you review them wherever they appear. In the unlikely event of any variation as between the provisions of these Terms and those other specific and additional provisions, the specific and additional provisions will prevail.</p>
HTML;

        $s2 = <<<'HTML'
<p>We retain full and complete title to all intellectual property, information and materials provided through the Website. If you agree to these Terms then you may download, print and/or copy content solely for your own personal, non-commercial use provided that you leave all copyright, trade mark and other proprietary notices intact and that you agree not to copy, download, store in any medium, display, adapt, modify, manipulate, translate, distribute, transmit or re–transmit, transfer, sell, re–publish any such material (including any software used in the creation of this Website) or to create any derivative works based on any such material for any purposes other than those noted above.</p>
<p>Modification of any of the materials or use of the materials for any other purpose will be an infringement of our intellectual property rights. No material from this Website may be commercially exploited in any way, without our prior written permission.</p>
HTML;

        $s3 = <<<'HTML'
<p>You are responsible for the connection between your PC and the Website. You must be at least thirteen (13) years old to use the Website. By accessing or using the Website, you confirm that you are not younger than 13. If you are aged between thirteen (13) and the age of majority in your place of residence, you may use the Website only under the supervision of your parent or legal guardian. If you are the parent or legal guardian and consent to your minor child’s access to and use of the Website, you agree to be bound by these Terms on behalf of yourself and your minor child.</p>
<p>While using the Website, you will not:</p>
<ul>
<li>Create a false identity or impersonate any person;</li>
<li>Transmit to or through the Website any advertisement, solicitation, junk mail or other unsolicited or unauthorised commercial or promotional content (unless expressly permitted in writing);</li>
<li>Disrupt or attempt to disrupt the proper working of the Website (e.g. by hacking into our server);</li>
<li>Restrict or inhibit any other person from using and enjoying the Website;</li>
<li>Use any spambot, bot net or other bot, scraper or other automated means to access the Website or transmit any virus, worm, Trojan or other malware to or through the Website;</li>
<li>Modify, adapt, sublicense, translate, sell, reverse engineer, decompile or disassemble any portion of any of the Website;</li>
<li>“Frame” or “mirror” any part of any of the Website unless you have prior written authorisation;</li>
<li>Post or transmit any material or engage in any other behaviour or activity that is false, misleading, unlawful, offensive, disruptive, harmful or otherwise objectionable (as determined by us); or</li>
<li>Assist any person in engaging in any of the activities described above.</li>
</ul>
<p>You acknowledge that you can be held legally liable for what you say or do online. We have the discretion to terminate your access to the Website without notice for any violation of the above rules. You undertake that all details and representations you provide to us for the purpose of registering on the Website are correct.</p>
HTML;

        $s4 = <<<'HTML'
<p>The Website contains links to other websites which are independent of us and are maintained by third parties. We are not responsible for the contents of any third party websites and shall not be liable for any damages or injury arising from the contents of such websites. Any links to other websites are provided as a convenience to you as a user of this website, and do not imply our endorsement of the linked websites or association with their operators.</p>
<p>Any third party that wishes to establish links to this Website should notify us of their intention prior to doing so. We reserve the right to deny permission for any such links to this Website. We recommend that you make appropriate enquiries and, if necessary, take legal and independent financial advice before entering into any transaction which may be based on any of the material contained in this Website.</p>
HTML;

        $s5 = <<<'HTML'
<p>Please make sure that you carefully read our Privacy and Cookies Policy to learn about the information that we collect on the Website and how we process it. Without limiting the terms of our Privacy and Cookies Policy, you understand that we do not and cannot guarantee that your use of the Website and/or the information provided by you through the Website will be private or secure. We are not responsible or liable to you for any lack of privacy or security you may experience.</p>
<p>You are responsible for using the precautions and security measures best suited for your situation and intended use of the Website. We reserve the right at all times to disclose any information as we deem necessary to satisfy any applicable law, regulation, legal process or governmental request.</p>
HTML;

        $s6 = <<<'HTML'
<p>Your use of the Website is at your own risk. We warrant that we have validly entered into these Terms and that we have the legal power to do so. We disclaim all express and implied warranties and conditions of any kind, including with regards to merchantability, fitness for a particular purpose, title, non-infringement, freedom from defects, uninterrupted use and all warranties implied from any use of dealings or usage of trade.</p>
<p>All information, services, and materials are provided “as is” and “as available” without warranty of any kind. We do not warrant that functions contained at this Website will be uninterrupted or error–free or that defects will be corrected or that this Website or the server that makes it available is free of any virus or other harmful elements.</p>
<p>For your own safety you should take regular back–up copies of data and use the latest virus checking software, and we cannot accept any liability arising from your failure to do so. You agree that in no event will we be liable for damages of any kind, including direct, indirect, special, exemplary, incidental, consequential or punitive damages (including, but not limited to loss of use, data or profits or business interruption), however caused and under any theory of liability, whether arising in any way in connection with these terms and whether in contract, strict liability or tort (including negligence or otherwise) even if we have been advised of the possibility of such damage for any other claim, demand or damages whatsoever resulting from or arising out of or in connection with your use of the Website.</p>
<p>The foregoing disclaimer of liability will not apply to the extent prohibited by applicable law. You acknowledge and agree that the above limitations of liability together with the other provisions in these Terms that limit liability are essential terms and that we would not be willing to grant you the rights set forth in these Terms but for your agreement to the above limitations of liability.</p>
HTML;

        $s7 = <<<'HTML'
<p>You agree to indemnify and defend us and our directors, officers, employees and agents from and against all losses, liabilities, actual or pending claims, actions, damages, expenses, costs of defense and reasonable legal fees brought against us by any third-party arising from your use of the Website or any violation of these Terms, the rights of a third-party or applicable law.</p>
<p>We reserve the right, at our own expense, to assume the exclusive defense and control of any matter subject to indemnification hereunder. In any event, no settlement that affects our rights or obligations may be made without our prior written approval.</p>
HTML;

        $s8 = <<<'HTML'
<p>These Terms, together with our Privacy and Cookies Policy, contain the entire understanding by and among us and you. If any provision of these Terms is or becomes unenforceable or invalid, the remaining provisions will continue with the same effect as if such unenforceable or invalid provision had not been inserted herein.</p>
<p>If we or you fail to perform any term hereof and the other party does not enforce such term, the failure to enforce on any occasion will not constitute a waiver of any term and will not prevent enforcement on any other occasion. These Terms shall be governed by English law and we and you agree to submit to the exclusive jurisdiction of the English courts. Nothing in these terms shall exclude liability for fraudulent misrepresentation.</p>
HTML;

        return [
            'hero_title' => __('Terms & Conditions.', 'culvers'),
            'hero_subtitle' => __('The small print aisle.', 'culvers'),
            'sections' => [
                [
                    'aside' => '<p>' . esc_html__('Effective Date: 08 September 2025', 'culvers') . '</p>',
                    'body' => $intro,
                ],
                [
                    'aside' => '<p>' . esc_html__('1. Changes to Terms and Additional terms', 'culvers') . '</p>',
                    'body' => $s1,
                ],
                [
                    'aside' => '<p>' . esc_html__('2. Content', 'culvers') . '</p>',
                    'body' => $s2,
                ],
                [
                    'aside' => '<p>' . esc_html__('3. Your Responsibilities and Acknowledgements', 'culvers') . '</p>',
                    'body' => $s3,
                ],
                [
                    'aside' => '<p>' . esc_html__('4. Third Party Websites', 'culvers') . '</p>',
                    'body' => $s4,
                ],
                [
                    'aside' => '<p>' . esc_html__('5. Privacy/Security', 'culvers') . '</p>',
                    'body' => $s5,
                ],
                [
                    'aside' => '<p>' . esc_html__('6. Disclaimers and Limitation of Liability', 'culvers') . '</p>',
                    'body' => $s6,
                ],
                [
                    'aside' => '<p>' . esc_html__('7. Indemnification', 'culvers') . '</p>',
                    'body' => $s7,
                ],
                [
                    'aside' => '<p>' . esc_html__('8. Miscellaneous', 'culvers') . '</p>',
                    'body' => $s8,
                ],
            ],
        ];
    }
}
