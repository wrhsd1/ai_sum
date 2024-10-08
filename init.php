<?php
class ai_sum extends Plugin
{
    private $host;
    
    public function about()
    {
        return array(
            1.0,
            "Generate AI summaries for articles using a custom API",
            "Your Name"
        );
    }

    public function flags()
    {
        return array(
            "needs_curl" => true
        );
    }

    public function save()
    {
        $this->host->set($this, "base_url", $_POST["base_url"]);
        $this->host->set($this, "api_key", $_POST["api_key"]);
        $this->host->set($this, "model", $_POST["model"]);
        echo __("AI Summary settings have been saved.");
    }

    public function init($host)
    {
        $this->host = $host;

        $host->add_hook($host::HOOK_ARTICLE_FILTER, $this);
        $host->add_hook($host::HOOK_PREFS_TAB, $this);
        $host->add_hook($host::HOOK_PREFS_EDIT_FEED, $this);
        $host->add_hook($host::HOOK_PREFS_SAVE_FEED, $this);
        $host->add_hook($host::HOOK_ARTICLE_BUTTON, $this);
        
        $host->add_filter_action($this, "ai_sum", __("AI Summary"));
    }

    public function get_js()
    {
        return file_get_contents(__DIR__ . "/init.js");
    }

    public function hook_article_button($line)
    {
        return "<i class='material-icons'
            style='cursor : pointer' onclick='Plugins.ai_sum.summarize(".$line["id"].")'
            title='".__('Generate AI Summary')."'>assistant</i>";
    }

    public function hook_prefs_tab($args)
    {
        if ($args != "prefFeeds") {
            return;
        }

        print "<div dojoType='dijit.layout.AccordionPane' 
            title=\"<i class='material-icons'>extension</i> ".__('AI Summary settings (ai_sum)')."\">";

        print "<form dojoType='dijit.form.Form'>";

        print "<script type='dojo/method' event='onSubmit' args='evt'>
            evt.preventDefault();
            if (this.validate()) {
                xhr.post(\"backend.php\", this.getValues(), (reply) => {
                    Notify.info(reply);
                })
            }
            </script>";

        print \Controls\pluginhandler_tags($this, "save");

        $base_url = $this->host->get($this, "base_url");
        $api_key = $this->host->get($this, "api_key");
        $model = $this->host->get($this, "model");

        print "<input dojoType='dijit.form.ValidationTextBox' required='1' name='base_url' value='$base_url'/>";
        print "&nbsp;<label for='base_url'>" . __("API Base URL") . "</label>";

        print "<br/><input dojoType='dijit.form.ValidationTextBox' required='1' name='api_key' value='$api_key'/>";
        print "&nbsp;<label for='api_key'>" . __("API Key") . "</label>";

        print "<br/><input dojoType='dijit.form.ValidationTextBox' required='1' name='model' value='$model'/>";
        print "&nbsp;<label for='model'>" . __("Model Name") . "</label>";

        print "<p><button dojoType=\"dijit.form.Button\" type=\"submit\" class=\"alt-primary\">".__('Save')."</button></p>";
        print "</form>";

        $enabled_feeds = $this->host->get($this, "enabled_feeds");
        if (!is_array($enabled_feeds)) {
            $enabled_feeds = array();
        }

        $enabled_feeds = $this->filter_unknown_feeds($enabled_feeds);
        $this->host->set($this, "enabled_feeds", $enabled_feeds);

        if (count($enabled_feeds) > 0) {
            print "<h3>" . __("Currently enabled for (click to edit):") . "</h3>";
            print "<ul class='panel panel-scrollable list list-unstyled'>";
            foreach ($enabled_feeds as $f) {
                print "<li><i class='material-icons'>rss_feed</i> <a href='#'
                    onclick='CommonDialogs.editFeed($f)'>".
                    Feeds::_get_title($f) . "</a></li>";
            }
            print "</ul>";
        }

        print "</div>";
    }

    public function hook_prefs_edit_feed($feed_id)
    {
        print "<header>".__("AI Summary")."</header>";
        print "<section>";
        
        $enabled_feeds = $this->host->get($this, "enabled_feeds");
        if (!is_array($enabled_feeds)) {
            $enabled_feeds = array();
        }
        
        $key = array_search($feed_id, $enabled_feeds);
        $checked = $key !== false ? "checked" : "";

        print "<fieldset>";
        print "<label class='checkbox'><input dojoType='dijit.form.CheckBox' type='checkbox' id='ai_sum_enabled' name='ai_sum_enabled' $checked>&nbsp;" . __('Generate AI summary for articles') . "</label>";
        print "</fieldset>";
        print "</section>";
    }

    public function hook_prefs_save_feed($feed_id)
    {
        $enabled_feeds = $this->host->get($this, "enabled_feeds");
        if (!is_array($enabled_feeds)) {
            $enabled_feeds = array();
        }
        
        $enable = checkbox_to_sql_bool($_POST["ai_sum_enabled"]);
        
        $key = array_search($feed_id, $enabled_feeds);
        
        if ($enable) {
            if ($key === false) {
                array_push($enabled_feeds, $feed_id);
            }
        } else {
            if ($key !== false) {
                unset($enabled_feeds[$key]);
            }
        }

        $this->host->set($this, "enabled_feeds", $enabled_feeds);
    }

    public function hook_article_filter($article)
    {
        $enabled_feeds = $this->host->get($this, "enabled_feeds");
        if (!is_array($enabled_feeds)) {
            return $article;
        }
        
        $key = array_search($article["feed"]["id"], $enabled_feeds);
        
        if ($key === false) {
            return $article;
        }
        
        return $this->process_article($article);
    }

    private function process_article($article)
    {
        $summary = $this->generate_summary($article["content"]);
        if ($summary) {
            $article["content"] = "<h4>AI Summary:</h4><p>" . $summary . "</p><hr>" . $article["content"];
        }
        return $article;
    }

    private function generate_summary($content)
    {
        $base_url = $this->host->get($this, "base_url");
        $api_key = $this->host->get($this, "api_key");
        $model = $this->host->get($this, "model");

        $data = array(
            'model' => $model,
            'messages' => array(
                array('role' => 'user', 'content' => "Summarize the following text in Chinese no more than 200 Chinese words:\n\n" . $content)
            )
        );

        $ch = curl_init($base_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if (isset($result['choices'][0]['message']['content'])) {
            return $result['choices'][0]['message']['content'];
        }

        return false;
    }

    public function api_version()
    {
        return 2;
    }

    private function filter_unknown_feeds($enabled_feeds)
    {
        $tmp = array();
        foreach ($enabled_feeds as $feed) {
            $sth = $this->pdo->prepare("SELECT id FROM ttrss_feeds WHERE id = ? AND owner_uid = ?");
            $sth->execute([$feed, $_SESSION['uid']]);
            if ($row = $sth->fetch()) {
                array_push($tmp, $feed);
            }
        }
        return $tmp;
    }

    public function summarize()
    {
        $article_id = (int) $_REQUEST["id"];

        $sth = $this->pdo->prepare("SELECT content FROM ttrss_entries WHERE id = ?");
        $sth->execute([$article_id]);

        if ($row = $sth->fetch()) {
            $summary = $this->generate_summary($row["content"]);
        }

        $result = array();
        if ($summary) {
            $result["summary"] = $summary;
        }

        print json_encode($result);
    }
}
