<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Laurent Bedubourg <lbedubourg@motion-twin.com>
 * @author   Kornel Lesiński <kornel@aardvarkmedia.co.uk>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id: $
 * @link     http://phptal.org/
 */


class UTF8Test extends PHPTAL_TestCase
{
    function testOverload()
    {
        $this->assertEquals(5,strlen('ąbć'),"mbstring.func_overload is not supported");
        $this->assertEquals('ą',substr('ąbć',0,2),"mbstring.func_overload is not supported");
        $str = 'ąbć';
        $this->assertEquals('b',$str[2],"mbstring.func_overload is not supported");
    }

    function testUmlaut()
    {
        $src = '<div class="box_title">Kopiëren van een rapport</div>';
        $res = $this->newPHPTAL()->setSource($src)->execute();
        $this->assertEquals($src,$res);
    }

    function testFile()
    {
        $this->assertContains(
            rawurldecode("%D0%97%D0%B0%D1%80%D0%B5%D0%B3%D0%B8%D1%81%D1%82%D1%80%D0%B8%D1%80%D1%83%D0%B9%D1%82%D0%B5%D1%81%D1%8C"),
            $this->newPHPTAL('input/utf8.xml')->execute()
        );
    }

    function testLipsum()
    {
        $tpl = $this->newPHPTAL()->setSource(rawurldecode('<?xml version="1.0" encoding="UTF-8"?>
                <test>Lørem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Iñtërnâtiônàlizætiøn, これは日本語のテキストです。読めますか. देखें हिन्दी कैसी नजर आती है। अरे वाह ये तो नजर आती है।. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</test>'))->execute();
    }

    function testValidUTF8_1()
    {
        /* Based on: UTF-8 decoder capability and stress test
           Markus Kuhn <http://www.cl.cam.ac.uk/~mgk25/> - 2003-02-19 */

        $tpl = $this->newPHPTAL()->setSource(rawurldecode('<?xml version="1.0" encoding="UTF-8"?>
            <test>
                                                                                             %7C
                1  Some correct UTF-8 text                                                    %7C
                                                                                              %7C
                You should see the Greek word %27kosme%27%3A       %22%CE%BA%E1%BD%B9%CF%83%CE%BC%CE%B5%22                          %7C
                                                                                              %7C
                2  Boundary condition test cases                                              %7C
                                                                                              %7C
                2.1  First possible sequence of a certain length                              %7C
                                                                                              %7C
                2.1.2  2 bytes %28U-00000080%29%3A        %22%C2%80%22                                       %7C
                2.1.3  3 bytes %28U-00000800%29%3A        %22%E0%A0%80%22                                       %7C
</test>'))->execute();
    }

    function testValidUTF8_2()
    {
        /* Based on: UTF-8 decoder capability and stress test
           Markus Kuhn <http://www.cl.cam.ac.uk/~mgk25/> - 2003-02-19 */

        $tpl = $this->newPHPTAL();
        $tpl->setSource(rawurldecode('<?xml version="1.0" encoding="UTF-8"?>
            <test>                                                                              %7C
            2.2  Last possible sequence of a certain length                               %7C
                                                                                          %7C
            2.2.1  1 byte  %28U-0000007F%29%3A        %22%7F%22
            2.2.2  2 bytes %28U-000007FF%29%3A        %22%DF%BF%22                                       %7C
            2.2.3  3 bytes %28U-0000FFFD%29%3A        %22%EF%BF%BD%22                                       %7C
          </test>'));
        $tpl->execute();
    }

    function testValidUTF8_3()
    {
        /* Based on: UTF-8 decoder capability and stress test
           Markus Kuhn <http://www.cl.cam.ac.uk/~mgk25/> - 2003-02-19 */

        $tpl = $this->newPHPTAL();
        $tpl->setSource(rawurldecode('<?xml version="1.0" encoding="UTF-8"?>
            <test>                                                                                %7C
                2.3  Other boundary conditions                                                %7C
                                                                                              %7C
                2.3.1  U-0000D7FF %3D ed 9f bf %3D %22%ED%9F%BF%22                                            %7C
                2.3.2  U-0000E000 %3D ee 80 80 %3D %22%EE%80%80%22                                            %7C
                2.3.3  U-0000FFFD %3D ef bf bd %3D %22%EF%BF%BD%22                                            %7C
                                                                                              %7C</test>'));
        $tpl->execute();
    }


    /**
     * @expectedException PHPTAL_ParserException
     */
    function testUnexpectedContinuationBytes()
    {
        $this->newPHPTAL()->setSource(rawurldecode('<p>                                                                            |
        Each unexpected continuation byte should be separately signalled as a         |
        malformed sequence of its own.                                                |
                                                                                      |
        3.1.1  First continuation byte 0x80: "%80"                                      |
        3.1.2  Last  continuation byte 0xbf: "%BF"                                      |
                                                                                      |
        3.1.3  2 continuation bytes: "%80%BF"                                             |
        3.1.4  3 continuation bytes: "%80%BF%80"                                            |
        3.1.5  4 continuation bytes: "%80%BF%80%BF"                                           |
        3.1.6  5 continuation bytes: "%80%BF%80%BF%80"                                          |
        3.1.7  6 continuation bytes: "%80%BF%80%BF%80%BF"                                         |
        3.1.8  7 continuation bytes: "%80%BF%80%BF%80%BF%80"                                        |</p>'))->execute();
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function testContinuations2()
    {
        $this->newPHPTAL()->setSource(rawurldecode('<p>
            3.1.9  Sequence of all 64 possible continuation bytes %280x80-0xbf%29:            |
                                                                                           |
                "%80%81%82%83%84%85%86%87%88%89%8A%8B%8C%8D%8E%8F                                                          |
                 %90%91%92%93%94%95%96%97%98%99%9A%9B%9C%9D%9E%9F                                                          |
                 %A0%A1%A2%A3%A4%A5%A6%A7%A8%A9%AA%AB%AC%AD%AE%AF                                                          |
                 %B0%B1%B2%B3%B4%B5%B6%B7%B8%B9%BA%BB%BC%BD%BE%BF"                                                         |</p>'))->execute();
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function testSequences2()
    {
        $this->newPHPTAL()->setSource(rawurldecode('<p>3.2  Lonely start characters                                                  |
                                                                                       |
         3.2.1  All 32 first bytes of 2-byte sequences %280xc0-0xdf%29%2C                    |
                each followed by a space character:                                    |
                                                                                       |
            "%C0 %C1 %C2 %C3 %C4 %C5 %C6 %C7 %C8 %C9 %CA %CB %CC %CD %CE %CF                                           |
             %D0 %D1 %D2 %D3 %D4 %D5 %D6 %D7 %D8 %D9 %DA %DB %DC %DD %DE %DF "                                         |</p>'))->execute();
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function test3ByteSquences()
    {
        $this->newPHPTAL()->setSource(rawurldecode('<p>                                                                           |
        3.2.2  All 16 first bytes of 3-byte sequences %280xe0-0xef%29%2C                    |
               each followed by a space character:                                    |
                                                                                      |
           "%E0 %E1 %E2 %E3 %E4 %E5 %E6 %E7 %E8 %E9 %EA %EB %EC %ED %EE %EF "                                         |</p>'))->execute();
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function test4ByteSequences()
    {
        $this->newPHPTAL()->setSource(rawurldecode('<p>
                                                                                      |
        3.2.3  All 8 first bytes of 4-byte sequences %280xf0-0xf7%29%2C                     |
               each followed by a space character:                                    |
                                                                                      |
           "%F0 %F1 %F2 %F3 %F4 %F5 %F6 %F7 "                                                         |
                                                                                      |</p>'))->execute();
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function test5ByteSequences()
    {
        $this->newPHPTAL()->setSource(rawurldecode('<p>

        3.2.4  All 4 first bytes of 5-byte sequences %280xf8-0xfb%29%2C                     |
               each followed by a space character:                                    |
                                                                                      |
           "%F8 %F9 %FA %FB "                                                                 |
                                                                                      |
        3.2.5  All 2 first bytes of 6-byte sequences %280xfc-0xfd%29%2C                     |
               each followed by a space character:                                    |
                                                                                      |
           "%FC %FD "                                                                     |</p>'))->execute();
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function testIncompleteSequence()
    {
        $this->newPHPTAL()->setSource(rawurldecode('<p>                                                                              |
        3.3  Sequences with last continuation byte missing                            |
                                                                                      |
        All bytes of an incomplete sequence should be signalled as a single           |
        malformed sequence%2C i.e.%2C you should see only a single replacement            |
        character in each of the next 10 tests. %28Characters as in section 2%29          |
                                                                                      |
        3.3.1  2-byte sequence with last byte missing %28U%2B0000%29:     "%C0"               |
        3.3.2  3-byte sequence with last byte missing %28U%2B0000%29:     "%E0%80"               |
        3.3.3  4-byte sequence with last byte missing %28U%2B0000%29:     "%F0%80%80"               |</p>'))->execute();
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function testLastByteMissing1()
    {
        $this->newPHPTAL()->setSource(rawurldecode('<p>
                                                                                      |


        3.3.4  5-byte sequence with last byte missing %28U%2B0000%29:     "%F8%80%80%80"               |
        3.3.5  6-byte sequence with last byte missing %28U%2B0000%29:     "%FC%80%80%80%80"               |
        3.3.6  2-byte sequence with last byte missing %28U-000007FF%29: "%DF"               |
        3.3.7  3-byte sequence with last byte missing %28U-0000FFFF%29: "%EF%BF"               |</p>'))->execute();
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function testLastByteMissing2()
    {
        $this->newPHPTAL()->setSource(rawurldecode('<p>
                                                                                      |

        3.3.8  4-byte sequence with last byte missing %28U-001FFFFF%29: "%F7%BF%BF"               |
        3.3.9  5-byte sequence with last byte missing %28U-03FFFFFF%29: "%FB%BF%BF%BF"               |
        3.3.10 6-byte sequence with last byte missing %28U-7FFFFFFF%29: "%FD%BF%BF%BF%BF"               |
                                                                                      |</p>'))->execute();
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function testConcatenation()
    {
        $this->newPHPTAL()->setSource(rawurldecode('<p>                                |

        3.4  Concatenation of incomplete sequences                                    |
                                                                                      |
        All the 10 sequences of 3.3 concatenated%2C you should see 10 malformed         |
        sequences being signalled:                                                    |
                                                                                      |
           "%C0%E0%80%F0%80%80%F8%80%80%80%FC%80%80%80%80%DF%EF%BF%F7%BF%BF%FB%BF%BF%BF%FD%BF%BF%BF%BF"                                                               |
                                                                                      |</p>'))->execute();
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function testImpossibleBytes()
    {
        $this->newPHPTAL()->setSource(rawurldecode('<p><!--                                                       |

        3.5  Impossible bytes                                                         |
                                                                                      |
        The following two bytes cannot appear in a correct UTF-8 string               |
                                                                                      |
        3.5.1  fe %3D "%FE"                                                               |
        3.5.2  ff %3D "%FF"                                                               |
        3.5.3  fe fe ff ff %3D "%FE%FE%FF%FF"                                                   |--></p>'))->execute();
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function testOverlong()
    {
        $this->newPHPTAL()->setSource(rawurldecode('<p>    <![CDATA[                                                                        |
        4.1.1 U%2B002F %3D c0 af             %3D "%C0%AF"                                        |
        4.1.2 U%2B002F %3D e0 80 af          %3D "%E0%80%AF"                                        |
        4.1.3 U%2B002F %3D f0 80 80 af       %3D "%F0%80%80%AF"                                        |]]></p>'))->execute();
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function testOverlong1()
    {
        $this->newPHPTAL()->setSource(rawurldecode('<p>

        4.1.4 U%2B002F %3D f8 80 80 80 af    %3D "%F8%80%80%80%AF"                                        |
        4.1.5 U%2B002F %3D fc 80 80 80 80 af %3D "%FC%80%80%80%80%AF"                                        |</p>'))->execute();
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function testOverlong2()
    {
        $this->newPHPTAL()->setSource(rawurldecode('<p>


        4.2.1  U-0000007F %3D c1 bf             %3D "%C1%BF"                                   |
        4.2.2  U-000007FF %3D e0 9f bf          %3D "%E0%9F%BF"                                   |</p>'))->execute();
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function testOverlong3()
    {
        $this->newPHPTAL()->setSource(rawurldecode('<p><![CDATA[
        4.2.3  U-0000FFFF %3D f0 8f bf bf       %3D "%F0%8F%BF%BF"                                   |
        4.2.4  U-001FFFFF %3D f8 87 bf bf bf    %3D "%F8%87%BF%BF%BF"                                   |
        4.2.5  U-03FFFFFF %3D fc 83 bf bf bf bf %3D "%FC%83%BF%BF%BF%BF"                                   |]]></p>'))->execute();
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function testNUL()
    {
        $this->newPHPTAL()->setSource(rawurldecode('<p>4.3.1  U%2B0000 %3D c0 80             %3D "%C0%80"                                       |
         4.3.2  U%2B0000 %3D e0 80 80          %3D "%E0%80%80"                                       |
         4.3.3  U%2B0000 %3D f0 80 80 80       %3D "%F0%80%80%80"                                       |</p>'))->execute();
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function testNUL2()
    {
        $this->newPHPTAL()->setSource(rawurldecode('<p title="4.3.4  U%2B0000 %3D f8 80 80 80 80    %3D "%F8%80%80%80%80"                                       |
         4.3.5  U%2B0000 %3D fc 80 80 80 80 80 %3D %FC%80%80%80%80%80   "/>'))->execute();
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function testUTF16()
    {
        $this->newPHPTAL()->setSource(rawurldecode('<p>5.1.1  U%2BD800 %3D ed a0 80 %3D "%ED%A0%80"                                                |
        5.1.2  U%2BDB7F %3D ed ad bf %3D "%ED%AD%BF"                                                |
        5.1.3  U%2BDB80 %3D ed ae 80 %3D "%ED%AE%80"                                                |
        5.1.4  U%2BDBFF %3D ed af bf %3D "%ED%AF%BF"                                                |
        5.1.5  U%2BDC00 %3D ed b0 80 %3D "%ED%B0%80"                                                |
        5.1.6  U%2BDF80 %3D ed be 80 %3D "%ED%BE%80"                                                |
        5.1.7  U%2BDFFF %3D ed bf bf %3D "%ED%BF%BF"                                                |</p>'))->execute();
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function testUTF16Paired()
    {
        $this->newPHPTAL()->setSource(rawurldecode('<p>5.2.1  U%2BD800 U%2BDC00 %3D ed a0 80 ed b0 80 %3D "%ED%A0%80%ED%B0%80"                               |
        5.2.2  U%2BD800 U%2BDFFF %3D ed a0 80 ed bf bf %3D "%ED%A0%80%ED%BF%BF"                               |
        5.2.3  U%2BDB7F U%2BDC00 %3D ed ad bf ed b0 80 %3D "%ED%AD%BF%ED%B0%80"                               |
        5.2.4  U%2BDB7F U%2BDFFF %3D ed ad bf ed bf bf %3D "%ED%AD%BF%ED%BF%BF"                               |
        5.2.5  U%2BDB80 U%2BDC00 %3D ed ae 80 ed b0 80 %3D "%ED%AE%80%ED%B0%80"                               |
        5.2.6  U%2BDB80 U%2BDFFF %3D ed ae 80 ed bf bf %3D "%ED%AE%80%ED%BF%BF"                               |
        5.2.7  U%2BDBFF U%2BDC00 %3D ed af bf ed b0 80 %3D "%ED%AF%BF%ED%B0%80"                               |
        5.2.8  U%2BDBFF U%2BDFFF %3D ed af bf ed bf bf %3D "%ED%AF%BF%ED%BF%BF"                               |</p>'))->execute();
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function testIllegalCodePositions()
    {
        $this->newPHPTAL()->setSource(rawurldecode('<p>5.3.1  U%2BFFFE %3D ef bf be %3D "%EF%BF%BE"                                                |
         5.3.2  U%2BFFFF %3D ef bf bf %3D "%EF%BF%BF"                                                |</p>'))->execute();
    }
}
