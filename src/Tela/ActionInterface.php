<?php namespace GM\Tela;

interface ActionInterface {

    /**
     * Getter for the id.
     *
     * @return string
     */
    public function getId();

    /**
     * Getter for action-stored blog id.
     *
     * @return string
     */
    public function getBlogId();

    /**
     * Getter for the action callback.
     *
     * @return callable
     */
    public function getCallback();

    /**
     * Get the validator instance stored in the action (or instantiate and return it).
     *
     * @return \GM\Tela\ActionArgsValidatorInterface|void
     */
    public function getValidator();

    /**
     * Get a variable from action context
     *
     * @param string $var Variable to get
     * @return mixed
     */
    public function getVar( $var );

    /**
     * Getter for action nonce.
     *
     * @return string
     */
    public function getNonce();

    /**
     * setter for action nonce.
     *
     * @param string $nonce
     * @return \GM\Tela\Action
     * @throws \InvalidArgumentException
     */
    public function setNonce( $nonce );

    /**
     * Setter for action id.
     *
     * @param string $id
     * @return \GM\Tela\Action
     */
    public function setId( $id );

    /**
     * Setter for action blog id.
     *
     * @param string $id
     * @return \GM\Tela\Action
     */
    public function setBlogId( $id );

    /**
     * Setter for action callback.
     *
     * @param callable $callback
     * @return \GM\Tela\Action
     */
    public function setCallback( $callback );

    /**
     * Set a validator object to be used for the action
     *
     * @param \GM\Tela\ActionArgsValidatorInterface $validator
     */
    public function setValidator( ActionArgsValidatorInterface $validator );

    /**
     * Set an array of variables in the action context.
     * Can be used only if no arguments is already set.
     *
     * @param array $args
     * @return \GM\Tela\Action
     */
    public function setArgs( Array $args = [ ] );

    /**
     * Set a variable in the action context.
     *
     * @param string $var
     * @param mixed $value
     * @return \GM\Tela\Action
     * @throws \InvalidArgumentException
     */
    public function setVar( $var );

    /**
     * Check if current action is public i.e. available in frontend for non logged users.
     * @return boolean
     */
    public function isPublic();

    /**
     * Ensure consistency for action arguemnts
     */
    public function validate();
}