import './bootstrap';
import { initTheme } from './theme';

initTheme();

// Create blend form that adds a new field to it.
document.addEventListener('DOMContentLoaded', () => {
   const container = document.querySelector('[data-testid="ingredients-container"]');
   const addButton = document.querySelector('[data-testid="add-ingredient"]');
   const template = document.querySelector('[data-testid="ingredient-template"]');

   if (!container || !addButton || !template) {
      return;
   }

   const existingRows = container.querySelectorAll('[data-testid="ingredient-row"]');
   let index = existingRows.length;

   addButton.addEventListener('click', () => {
      const html = template.innerHTML.replaceAll('__INDEX__', index);
      container.insertAdjacentHTML('beforeend', html);

      const lastContainer = container.lastElementChild;
      if (lastContainer?.dataset?.testid === 'ingredient-template-row') {
         lastContainer.dataset.testid = 'ingredient-row';
      }
      index++;
      //test
   });
});
