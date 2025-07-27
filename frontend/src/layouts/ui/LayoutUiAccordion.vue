<script setup>
const panelIconStyle = (collapsed) => {
  if (!collapsed) {
    return {
      transform: 'rotate(45deg)',
    }
  }
}

// Определение вариантов и состояний для аккордеона
const accordionStates = ['Default']
const accordionVariants = ['Accordion', 'Panel', 'Fieldset', 'ScrollPanel']
</script>

<template>
  <LayoutUiTemplate
    title="Accordion / Panel / Fieldset"
    :states="accordionStates"
    :variants="accordionVariants">
    <!-- Accordion -->
    <template #accordion-default>
      <Accordion :value="['0']" multiple>
        <template #expandicon>
          <i-custom-arrow-down style="transform: rotate(180deg)" />
        </template>
        <template #collapseicon>
          <i-custom-arrow-down />
        </template>
        <AccordionPanel value="0">
          <AccordionHeader> Заголовок I</AccordionHeader>
          <AccordionContent>
            <p class="m-0">
              Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do
              eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut
              enim ad minim veniam, quis nostrud exercitation ullamco laboris
              nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in
              reprehenderit in voluptate velit esse cillum dolore eu fugiat
              nulla pariatur. Excepteur sint occaecat cupidatat non proident,
              sunt in culpa qui officia deserunt mollit anim id est laborum.
            </p>
          </AccordionContent>
        </AccordionPanel>
        <AccordionPanel value="1">
          <AccordionHeader>Заголовок II</AccordionHeader>
          <AccordionContent>
            <p class="m-0">
              Sed ut perspiciatis unde omnis iste natus error sit voluptatem
              accusantium doloremque laudantium, totam rem aperiam, eaque ipsa
              quae ab illo inventore veritatis et quasi architecto beatae vitae
              dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit
              aspernatur aut odit aut fugit, sed quia consequuntur magni dolores
              eos qui ratione voluptatem sequi nesciunt. Consectetur, adipisci
              velit, sed quia non numquam eius modi.
            </p>
          </AccordionContent>
        </AccordionPanel>
        <AccordionPanel value="2" disabled>
          <AccordionHeader>Заголовок III</AccordionHeader>
          <AccordionContent>
            <p class="m-0">
              At vero eos et accusamus et iusto odio dignissimos ducimus qui
              blanditiis praesentium voluptatum deleniti atque corrupti quos
              dolores et quas molestias excepturi sint occaecati cupiditate non
              provident, similique sunt in culpa qui officia deserunt mollitia
              animi, id est laborum et dolorum fuga. Et harum quidem rerum
              facilis est et expedita distinctio. Nam libero tempore, cum soluta
              nobis est eligendi optio cumque nihil impedit quo minus.
            </p>
          </AccordionContent>
        </AccordionPanel>
      </Accordion>
    </template>

    <!-- Panel -->
    <template #panel-default>
      <Panel header="Заголовок" toggleable>
        <template #toggleicon="{ collapsed }">
          <Transition>
            <i-custom-plus :style="panelIconStyle(collapsed)" />
          </Transition>
        </template>
        <p class="m-0">
          Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do
          eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad
          minim veniam, quis nostrud exercitation ullamco laboris nisi ut
          aliquip ex ea commodo consequat. Duis aute irure dolor in
          reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla
          pariatur. Excepteur sint occaecat cupidatat non proident, sunt in
          culpa qui officia deserunt mollit anim id est laborum.
        </p>
      </Panel>
    </template>

    <!-- Fieldset -->
    <template #fieldset-default>
      <Fieldset legend="Заголовок" toggleable>
        <template #toggleicon="{ collapsed }">
          <Transition>
            <i-custom-plus :style="panelIconStyle(collapsed)" />
          </Transition>
        </template>
        <p class="m-0">
          Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do
          eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad
          minim veniam, quis nostrud exercitation ullamco laboris nisi ut
          aliquip ex ea commodo consequat. Duis aute irure dolor in
          reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla
          pariatur. Excepteur sint occaecat cupidatat non proident, sunt in
          culpa qui officia deserunt mollit anim id est laborum.
        </p>
      </Fieldset>
    </template>

    <!-- ScrollPanel -->
    <template #scrollpanel-default>
      <ScrollPanel class="scrollpanel">
        <span class="fw-semibold">ScrollPanel</span>
        <p>
          ScrollPanel is a cross browser, lightweight and skinnable alternative
          to native browser scrollbar. <br /><br />Lorem ipsum dolor sit amet,
          consectetur adipiscing elit, sed do eiusmod tempor incididunt ut
          labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud
          exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
          Duis aute irure dolor in reprehenderit in voluptate velit esse cillum
          dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
          proident, sunt in culpa qui officia deserunt mollit anim id est
          laborum.
        </p>
      </ScrollPanel>
    </template>
  </LayoutUiTemplate>
</template>

<style lang="scss" scoped>
.scrollpanel {
  width: 100%;
  height: 8rem;
}
</style>
