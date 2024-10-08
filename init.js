/* global xhr, App, Plugins, Article, Notify */

Plugins.ai_sum = {
  orig_content: "data-ai-sum-orig-content",
  self: this,
  summarize: function (id) {
    const content = App.find(
      App.isCombinedMode()
        ? `.cdm[data-article-id="${id}"] .content-inner`
        : `.post[data-article-id="${id}"] .content`
    );

    if (content.hasAttribute(self.orig_content)) {
      content.innerHTML = content.getAttribute(self.orig_content);
      content.removeAttribute(self.orig_content);

      if (App.isCombinedMode()) Article.cdmMoveToId(id);

      return;
    }

    Notify.progress("Summarizing, please wait...");

    xhr.json(
      "backend.php",
      App.getPhArgs("ai_sum", "summarize", { id: id }),
      (reply) => {
        if (content && reply.summary) {
          content.setAttribute(self.orig_content, content.innerHTML);
          content.innerHTML = "<h4>AI Summary:</h4><p>" + reply.summary + "</p><hr>" + content.innerHTML;
          Notify.close();

          if (App.isCombinedMode()) Article.cdmMoveToId(id);
        } else {
          Notify.error("Unable to generate summary for this article");
        }
      }
    );
  },
};
