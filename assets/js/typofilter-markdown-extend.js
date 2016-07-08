/**
 * Script to extend Markdown editor for Blog post form
 *  - add button for Typofilter
 */

+function ($) {
  $(document).ready(function () {
    var editor = $('[data-control="markdowneditor"]').data('oc.markdownEditor');

    var button = {
      label: 'Typofilter',
      icon: 'magic',
      insertAfter: 'formatting',
      action: 'runTypofilter',
      template: '$1'
    };

    /**
     *
     * Markdown editor method to process selected text with typofilter
     *
     * @param template
     */
    editor.runTypofilter = function (template) {
      var editor = this.editor,
        pos = this.editor.getCursorPosition(),
        text = editor.session.getTextRange(editor.selection.getRange()).trim();

      if (!text.length) {
        editor.selection.selectAll();
        text = editor.session.getTextRange(editor.selection.getRange()).trim();
      }

      text = Typographus.process(text);

      editor.insert(template.replace('$1', text));
      editor.moveCursorToPosition(pos);

      if (template.indexOf('$1') != -1) {
        editor.navigateRight(template.indexOf('$1'))
      }

      editor.focus()
    };

    //add button to editor
    editor.addToolbarButton('typofilter', button);
  });

}(window.jQuery);
